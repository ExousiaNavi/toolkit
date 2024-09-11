import os
import asyncio
import json
import httpx
import logging
from pathlib import Path
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError

# Configure logging
logging.basicConfig(level=logging.INFO)

class AdxadScraper:
    def __init__(self, keywords, email, password, link, creative_id, dashboard, platform):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
        self.session_dir = "sessions"
        self.session_file = self.get_session_file()

    def get_yesterday_session_file(self):
        """Get the session file path for yesterday's date."""
        yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
        session_file_name = f"{self.keywords.replace(' ', '_')}_{yesterday}.json"
        return os.path.join(self.session_dir, session_file_name)

    def get_session_file(self):
        """Get the session file path for today's date."""
        today = datetime.now().strftime('%Y-%m-%d')
        session_file_name = f"{self.keywords.replace(' ', '_')}_{today}.json"
        return os.path.join(self.session_dir, session_file_name)

    async def run(self):
        """Run the main automation process."""
        # Create sessions directory if it doesn't exist
        os.makedirs(self.session_dir, exist_ok=True)

        # Delete yesterday's session file if it exists
        yesterday_session_file = self.get_yesterday_session_file()
        if Path(yesterday_session_file).exists():
            os.remove(yesterday_session_file)
            logging.info(f"Deleted yesterday's session file: {yesterday_session_file}")

        async with async_playwright() as p:
            max_retries = 10
            attempt = 0

            while attempt < max_retries:
                try:
                    attempt += 1
                    logging.info(f"Attempt {attempt} of {max_retries}...")

                    # Load session if it exists
                    if Path(self.session_file).exists():
                        state = json.loads(Path(self.session_file).read_text())
                        browser = await p.chromium.launch(headless=False)
                        context = await browser.new_context(storage_state=state)
                        page = await context.new_page()
                        await page.goto(self.link)
                        logging.info("Loaded existing session.")
                    else:
                        browser = await p.chromium.launch(headless=False)
                        context = await browser.new_context()
                        page = await context.new_page()
                        await page.goto(self.link)

                        # Execute functions in a try-except block to handle potential errors
                        if not await self.fill_login_form(page):
                            logging.error("Failed to fill the login form.")
                            continue  # Retry if login fails

                        if not await self.submit_form(page):
                            logging.error("Failed to submit the login form.")
                            continue  # Retry if form submission fails

                        if not await self.set_yesterdays_date(page):
                            logging.error("Failed to set yesterday's date.")
                            continue  # Retry if setting the date fails

                        # Save the session after a successful login
                        state = await context.storage_state()
                        Path(self.session_file).write_text(json.dumps(state))
                        logging.info("Session saved.")

                    # Wait for the page to load after login, and retrieve the token from local storage or cookies
                    token = await page.evaluate("localStorage.getItem('token')")
                    logging.warning(f"Token: {token}...")

                    if not token:
                        logging.error("Token not found. Login may have failed.")
                        continue  # Retry if the token is not found

                    # Fetch campaign data
                    campaign_data = await self.fetch_campaign_data(token)
                    filtered_data = await self.filter_data(campaign_data['data'])
                    logging.info(filtered_data)
                    return filtered_data
                    break  # Exit the loop if everything was successful

                except Exception as e:
                    logging.error(f"[ERROR] An unexpected error occurred: {e}")

                    if attempt >= max_retries:
                        logging.error("Maximum retry attempts reached. Exiting.")
                        break

                finally:
                    if 'browser' in locals():  # Ensure browser is defined
                        await context.close()
                        await browser.close()

    async def filter_data(self, raw_data):
        try:
            filtered_campaigns = []

            for campaign in raw_data:
                campaign_aid = campaign['aid']
                logging.info(f"Campaign ID: {campaign_aid}, Creative ID: {self.creative_id}")
                if str(campaign_aid) in map(str, self.creative_id):
                    data = {
                        'creative_id': campaign.get('aid', 0),
                        'Impressions': campaign.get('sum_impressions', 0),
                        'Clicks': campaign.get('clicks', 0),
                        'Spending': campaign.get('spend', 0)
                    }
                    filtered_campaigns.append(data)

            logging.info(f"Filtered Campaigns: {filtered_campaigns}")
            return filtered_campaigns
        except PlaywrightTimeoutError:
            logging.error('Filtering error...')
            return False

    async def fill_login_form(self, page):
        try:
            await page.fill('input[data-test="auth-form-email-input"]', self.email)
            await page.fill('input[data-test="auth-form-password-input"]', self.password)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        try:
            await page.click('button[data-test="auth-form-submit-btn"]')
            return True
        except PlaywrightTimeoutError:
            logging.error("Timeout during form submission.")
            return False

    async def set_yesterdays_date(self, page):
        try:
            await page.wait_for_selector('div.adxad-calendar__select', state='visible', timeout=60000)
            element = page.locator('div.adxad-calendar__select')
            bounding_box = await element.bounding_box()

            if bounding_box is None:
                print("Element not fully loaded or visible")
                return False

            await page.screenshot(path="before_click.png")
            await element.click(force=True)
            print("Click on date range picker successful")

            await page.screenshot(path="after_click.png")
            await page.wait_for_selector("div.adxad-options", state='visible', timeout=60000)
            print("Date picker dropdown is visible")

            await asyncio.sleep(2)
            await page.click('div.adxad-options__option:has-text("Yesterday")')
            print("Yesterday's date selected")

            await page.wait_for_selector('button:has-text("Apply")', state='visible')
            await page.click('button:has-text("Apply")', force=True)
            print("Applied the selected date range")

            await asyncio.sleep(5)
            return True

        except Exception as e:
            print(f"Error encountered: {e}")
            return False

    async def fetch_campaign_data(self, token_json):
        # Calculate yesterday's date
        yesterday = (datetime.now() - timedelta(days=1)).strftime('%m/%d/%Y')
        url = "https://td.adxad.com/api/v1/campaign/grid"
        params = {
            'limit': '100',
            'page': '1',
            'filter[from]': yesterday,
            'filter[to]': yesterday
        }
        token = json.loads(token_json)

        headers = {
            'Authorization': f'Bearer {token["access_token"]}'
        }

        async with httpx.AsyncClient() as client:
            response = await client.get(url, params=params, headers=headers)

            if response.status_code == 200:
                return response.json()
            else:
                logging.error(f"Failed to fetch campaign data: {response.status_code}")
                return None