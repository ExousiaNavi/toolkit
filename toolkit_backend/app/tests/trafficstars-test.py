import asyncio
import logging
import os
import json
from pathlib import Path
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError

# Configure logging
logging.basicConfig(level=logging.INFO)

class TrafficStarsAutomation:
    def __init__(self, keywords, email, password, link, creative_id, dashboard, platform):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
        self.session_dir = "sessions"
        self.browser = None
        self.context = None
        self.page = None

    def get_yesterday_session_file(self):
        yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
        keywords_str = "_".join(self.keywords)  # Convert tuple to string with underscores
        session_file_name = f"{keywords_str}_{yesterday}.json"
        return os.path.join(self.session_dir, session_file_name)

    def get_today_session_file(self):
        today = datetime.now().strftime('%Y-%m-%d')
        keywords_str = "_".join(self.keywords)  # Convert tuple to string with underscores
        session_file_name = f"{keywords_str}_{today}.json"
        return os.path.join(self.session_dir, session_file_name)

    async def run(self):
        os.makedirs(self.session_dir, exist_ok=True)
        today_session_file = self.get_today_session_file()

        # Delete yesterday's session file if it exists
        yesterday_session_file = self.get_yesterday_session_file()
        if Path(yesterday_session_file).exists():
            os.remove(yesterday_session_file)
            logging.info(f"Deleted yesterday's session file: {yesterday_session_file}")

        async with async_playwright() as p:
            try:
                if Path(today_session_file).exists():
                    state = json.loads(Path(today_session_file).read_text())
                    self.browser = await p.chromium.launch(headless=False)
                    self.context = await self.browser.new_context(storage_state=state)
                    self.page = await self.context.new_page()
                    await self.page.goto("https://admin.trafficstars.com/advertisers/campaigns/")
                    logging.info("Loaded existing session.")
                else:
                    self.browser = await p.chromium.launch(headless=False)
                    self.context = await self.browser.new_context()
                    self.page = await self.context.new_page()
                    await self.page.goto(self.link)
                    
                    # Execute functions in a try-except block to handle potential errors
                    if not await self.fill_login_form():
                        return {"status": 400, "text": "Failed to fill the login form."}
                    
                    if not await self.submit_form():
                        return {"status": 400, "text": "Failed to submit the login form."}
                    
                if not await self.navigate_to_report():
                    return {"status": 400, "text": "Failed to navigate to the report page."}
                
                if not await self.set_yesterdays_date():
                    return {"status": 400, "text": "Failed to set yesterday's date."}

                # Extract table data
                table_data = await self.extract_table_data()
                print(table_data)

                # Save session after a successful login
                state = await self.context.storage_state()
                Path(today_session_file).write_text(json.dumps(state))
                logging.info("Session saved.")
            except Exception as e:
                logging.error(f"[ERROR] An unexpected error occurred: {e}")
            finally:
                if self.context:
                    await self.context.close()
                if self.browser:
                    await self.browser.close()

    async def fill_login_form(self):
        try:
            await self.page.fill('input[name="username"]', self.email)
            await self.page.fill('input[name="password"]', self.password)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self):
        try:
            await self.page.click('#kc-login')
            return True
        except PlaywrightTimeoutError:
            logging.error("Timeout during form submission.")
            return False

    async def navigate_to_report(self):
        try:
            reports_xpath = '//*[@id="top_menu"]/div/div[1]/div[2]/div/a[2]/button'
            await self.page.click(f'xpath={reports_xpath}')
            logging.info("Report button clicked.")
            return True
        except PlaywrightTimeoutError:
            logging.error("Navigation failed")
            return False

    async def set_yesterdays_date(self):
        try:
            await self.page.wait_for_selector('#reportrange > span', state='visible', timeout=60000)
            element = self.page.locator('#reportrange > span')
            bounding_box = await element.bounding_box()

            if bounding_box is None:
                logging.error("Element not fully loaded or visible")
                return False

            await element.click(force=True)
            logging.info("Click on date range picker successful")
            await self.page.wait_for_selector("body > div.daterangepicker.dropdown-menu.opensright", state='visible', timeout=60000)
            logging.info("Date picker dropdown is visible")

            await self.page.click('li:has-text("Yesterday")')
            logging.info("Yesterday's date selected")
            await self.page.wait_for_selector("#main-content > section > div:nth-child(3) > section > div > div > form > div.pull-right > input", state='visible')
            await self.page.click("#main-content > section > div:nth-child(3) > section > div > div > form > div.pull-right > input", force=True)
            logging.info("Applied the selected date range")

            return True

        except Exception as e:
            logging.error(f"Error encountered: {e}")
            return False

    async def extract_table_data(self):
        all_data = []
        print(self.creative_id)
         # Extract the list from the tuple
        creative_ids = self.creative_id[0] if self.creative_id else []
        print(creative_ids)
        for cid in creative_ids:
            data = await self.extract_data_for_creative_id(cid)
            all_data.append(data)
        return all_data

    async def extract_data_for_creative_id(self, ci, max_retries=3):
        print(ci)
        retry_count = 0
        while retry_count < max_retries:
            try:
                await self.page.wait_for_load_state('networkidle')
                await self.page.wait_for_selector('table.table.table-advance.campaign-advertisers-table.dataTable.no-footer')
                await asyncio.sleep(5)  # Optional: Adding a short delay to ensure the table is fully loaded

                table = await self.page.query_selector('table.table.table-advance.campaign-advertisers-table.dataTable.no-footer')
                header_texts = await self.extract_table_headers(table)

                id_index = header_texts.index("ID")
                impressions_index = header_texts.index("IMPRS")
                clicks_index = header_texts.index("CLICKS")
                costs_index = header_texts.index("COSTS (USD)")

                rows = await table.query_selector_all('tbody:first-of-type tr')

                for row in rows:
                    c_id, impression, clicks, spending = await self.extract_row_data(row, id_index, impressions_index, clicks_index, costs_index)
                    print(c_id, impression, clicks, spending)
                    if c_id == ci:
                        data = {
                            'creative_id': ci,
                            'Impressions': impression,
                            'Clicks': clicks,
                            'Spending': spending
                        }
                        return data

            except Exception as e:
                retry_count += 1
                logging.error(f"Error processing CID '{ci}' on attempt {retry_count}: {e}")
                if retry_count >= max_retries:
                    logging.error(f"Max retries reached for CID '{ci}'. Skipping.")
                    return {
                        'creative_id': ci,
                        'Impressions': '0',
                        'Clicks': '0',
                        'Spending': '0'
                    }

            finally:
                logging.info(f"Task attempt {retry_count} completed for CID '{ci}'.")

        logging.info(f"Finished processing CID '{ci}'.")

    async def extract_table_headers(self, table):
        try:
            headers = []
            header_elements = await table.query_selector_all('thead tr th')
            for header in header_elements:
                headers.append(await header.inner_text())
            return headers
        except Exception as e:
            logging.error(f"Error extracting table headers: {e}")
            raise

    async def extract_row_data(self, row, id_index, impressions_index, clicks_index, spending_index):
        try:
            columns = await row.query_selector_all('td')
            return (
                await columns[id_index].inner_text(),
                await columns[impressions_index].inner_text(),
                await columns[clicks_index].inner_text(),
                await columns[spending_index].inner_text()
            )
        except Exception as e:
            logging.error(f"Error extracting row data: {e}")
            raise

# Example usage:
if __name__ == "__main__":
    keywords="trastarpkr",
    email="abiralmilan1014@gmail.com",
    password="B@j!qwe@4444",
    link="https://id.trafficstars.com/realms/trafficstars/protocol/openid-connect/auth?scope=openid&redirect_uri=http%3A%2F%2Fadmin.trafficstars.com%2Faccounts%2Fauth%2F%3Fnext%3Dhttps%3A%2F%2Fadmin.trafficstars.com%2F&response_type=code&client_id=web-app",
    creative_id=['710956', '783520'],
    dashboard="dashboard link",
    platform="platform links",

    automation = TrafficStarsAutomation(keywords, email, password, link, creative_id, dashboard, platform)
    asyncio.run(automation.run())


# if __name__ == "__main__":
#     asyncio.run(main(
#         keywords="trastarpkr",
#         email="abiralmilan1014@gmail.com",
#         password="B@j!qwe@4444",
#         link="https://id.trafficstars.com/realms/trafficstars/protocol/openid-connect/auth?scope=openid&redirect_uri=http%3A%2F%2Fadmin.trafficstars.com%2Faccounts%2Fauth%2F%3Fnext%3Dhttps%3A%2F%2Fadmin.trafficstars.com%2F&response_type=code&client_id=web-app",
#         creative_id=['710956', '783520']
#     ))
