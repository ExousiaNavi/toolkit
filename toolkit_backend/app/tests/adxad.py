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

# SESSION_FILE = "session.json"
def get_yesterday_session_file(keywords):
    """Get the session file path for yesterday's date."""
    SESSION_DIR = "sessions"
    yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
    session_file_name = f"{keywords.replace(' ', '_')}_{yesterday}.json"
    return os.path.join(SESSION_DIR, session_file_name)

async def main(keywords, email, password, link, creative_id):
    SESSION_DIR = "sessions"  # Define the session directory
    today = datetime.now().strftime('%Y-%m-%d')
    session_file_name = f"{keywords.replace(' ', '_')}_{today}.json"  # Include today's date in the filename
    SESSION_FILE = os.path.join(SESSION_DIR, session_file_name)  # Define the session file path

    # Create sessions directory if it doesn't exist
    os.makedirs(SESSION_DIR, exist_ok=True)
    
    # Delete yesterday's session file if it exists
    yesterday_session_file = get_yesterday_session_file(keywords)
    if Path(yesterday_session_file).exists():
        os.remove(yesterday_session_file)
        logging.info(f"Deleted yesterday's session file: {yesterday_session_file}")
        
    async with async_playwright() as p:
        max_retries = 10  # Set the maximum number of retries
        attempt = 0

        while attempt < max_retries:
            try:
                attempt += 1
                logging.info(f"Attempt {attempt} of {max_retries}...")
        
                # Load session if it exists
                if Path(SESSION_FILE).exists():
                    state = json.loads(Path(SESSION_FILE).read_text())
                    browser = await p.chromium.launch(headless=False)
                    context = await browser.new_context(storage_state=state)
                    page = await context.new_page()
                    await page.goto(link)
                    logging.info("Loaded existing session.")
                else:
                    browser = await p.chromium.launch(headless=False)
                    context = await browser.new_context()
                    page = await context.new_page()
                    await page.goto(link)
                    
                    # Execute functions in a try-except block to handle potential errors
                    if not await fill_login_form(page, email, password):
                        logging.error("Failed to fill the login form.")
                        continue  # Retry if login fails
                    
                    if not await submit_form(page):
                        logging.error("Failed to submit the login form.")
                        continue  # Retry if form submission fails
                        
                    if not await set_yesterdays_date(page):
                        logging.error("Failed to set yesterday's date.")
                        continue  # Retry if setting the date fails

                    # Save the session after a successful login
                    state = await context.storage_state()
                    Path(SESSION_FILE).write_text(json.dumps(state))
                    logging.info("Session saved.")

                # Wait for the page to load after login, and retrieve the token from local storage or cookies
                token = await page.evaluate("localStorage.getItem('token')")  # Update the key as needed
                logging.warning(f"Token: {token}...")

                if not token:
                    logging.error("Token not found. Login may have failed.")
                    continue  # Retry if the token is not found

                # Fetch campaign data
                campaign_data = await fetch_campaign_data(token)
                filteredData = await filter_data(campaign_data['data'], creative_id)
                logging.info(filteredData)

                break  # Exit the loop if everything was successful

            except Exception as e:
                logging.error(f"[ERROR] An unexpected error occurred: {e}")
                
                if attempt >= max_retries:
                    logging.error("Maximum retry attempts reached. Exiting.")
                    break

            finally:
                if 'browser' in locals():  # Ensure browser is defined
                    await browser.close()

        # await asyncio.sleep(5)

#filter data
async def filter_data(rawData, creativeId):
    try:
        # Assuming 'campaign_data' is the response received from the fetch_campaign_data function
        filtered_campaigns = []
        
        for campaign in rawData:
            campaign_aid = campaign['aid']  # Get the aid from the campaign data
            logging.info(f"Campaign ID: {campaign['aid']}, Creative ID: {creativeId}")
            # Check if the 'aid' is in the creative_id list
            if str(campaign_aid) in map(str, creativeId):
                # Extract and format the desired data
                data = {
                    'creative_id': campaign.get('aid', 0),  # Use campaign_aid as the creative_id
                    'Impressions': campaign.get('sum_impressions', 0),  # Default to 0 if key doesn't exist
                    'Clicks': campaign.get('clicks', 0),  # Default to 0 if key doesn't exist
                    'Spending': campaign.get('spend', 0)  # Default to 0 if key doesn't exist
                }
                filtered_campaigns.append(data)
        
        # Log or return the filtered campaigns
        logging.info(f"Filtered Campaigns: {filtered_campaigns}")
        # You can also return filtered_campaigns if needed
        
        return filtered_campaigns
    except PlaywrightTimeoutError:
        logging.error('filterring error...')
        return False
async def wait_for_navigation(page):
    try:
        await page.wait_for_load_state('networkidle')
        return True
    except PlaywrightTimeoutError:
        logging.error("Navigation wait timeout.")
        return False

async def fill_login_form(page, email, password):
    try:
        await page.fill('input[data-test="auth-form-email-input"]', email)
        await page.fill('input[data-test="auth-form-password-input"]', password)
        return True
    except Exception as e:
        logging.error(f"Error filling login form: {e}")
        return False

async def submit_form(page):
    try:
        await page.click('button[data-test="auth-form-submit-btn"]')
        return True
    except PlaywrightTimeoutError:
        logging.error("Timeout during form submission.")
        return False

async def set_yesterdays_date(page):
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

async def fetch_campaign_data(token_json):
    url = "https://td.adxad.com/api/v1/campaign/grid"
    params = {
        'limit': '10',
        'page': '1',
        'filter[from]': '08/26/2024',
        'filter[to]': '08/26/2024'
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

if __name__ == "__main__":
    asyncio.run(main(
        keywords="adxadbdt",
        email="bo.cc@chengyi-1.com",
        password="B@j!09876**1",
        link="https://td.adxad.com/auth/login?lang=en",
        creative_id=['55347']
    ))
