# scraper/adcash_scraper.py

import asyncio
import logging
import os
import json
from pathlib import Path
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError

# Configure logging
logging.basicConfig(level=logging.INFO)

class AdcashScraper:
    def __init__(self, keywords, email, password, link, creative_id, dashboard, platform):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
        self.session_dir = "sessions"
        self.today = datetime.now().strftime('%Y-%m-%d')
        self.session_file_name = f"{self.keywords.replace(' ', '_')}_{self.today}.json"
        self.session_file_path = os.path.join(self.session_dir, self.session_file_name)
        
        # Create sessions directory if it doesn't exist
        os.makedirs(self.session_dir, exist_ok=True)
        
        # Delete yesterday's session file if it exists
        self.delete_yesterdays_session()

    def get_yesterday_session_file(self):
        """Get the session file path for yesterday's date."""
        yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
        session_file_name = f"{self.keywords.replace(' ', '_')}_{yesterday}.json"
        return os.path.join(self.session_dir, session_file_name)

    def delete_yesterdays_session(self):
        """Delete yesterday's session file if it exists."""
        yesterday_session_file = self.get_yesterday_session_file()
        if Path(yesterday_session_file).exists():
            os.remove(yesterday_session_file)
            logging.info(f"Deleted yesterday's session file: {yesterday_session_file}")

    async def run(self):
        """Main function to run the scraper."""
        async with async_playwright() as p:
            try:
                if Path(self.session_file_path).exists():
                    state = json.loads(Path(self.session_file_path).read_text())
                    browser = await p.chromium.launch(headless=False)
                    context = await browser.new_context(storage_state=state)
                    page = await context.new_page()
                    await page.goto(self.dashboard)
                    logging.info("Loaded existing session.")
                else:
                    browser = await p.chromium.launch(headless=False)
                    context = await browser.new_context()
                    page = await context.new_page()
                    await page.goto(self.link)
                    
                    # Execute functions in a try-except block to handle potential errors
                    if not await self.fill_login_form(page):
                        return {"status": 400, "text": "Failed to fill the login form."}
                        
                    if not await self.submit_form(page):
                        return {"status": 400, "text": "Failed to submit the login form."}

                if not await self.navigate_to_report(page):
                    return {"status": 400, "text": "Failed to navigate to the report page."}

                if not await self.set_yesterdays_date(page):
                    return {"status": 400, "text": "Failed to set yesterday's date."}
                
                # Call the function to extract the data
                table_data = await self.extract_table_data(page)
                logging.info(table_data)
                
                # Save the session after a successful login
                state = await context.storage_state()
                Path(self.session_file_path).write_text(json.dumps(state))
                logging.info("Session saved.")
                
                return table_data

            except Exception as e:
                logging.error(f"[ERROR] An unexpected error occurred: {e}")
            finally:
                await context.close()
                await browser.close()
                
            await asyncio.sleep(5)

    async def wait_for_navigation(self, page):
        try:
            await page.wait_for_load_state('networkidle')
            return True
        except PlaywrightTimeoutError:
            logging.error("Navigation wait timeout.")
            return False

    async def fill_login_form(self, page):
        try:
            await page.click('body > header > div > nav.button-menu > a:nth-child(1)')
            await page.fill('input[name="username"]', self.email)
            await page.fill('input[name="password"]', self.password)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        try:
            await page.click('#kc-login')
            await self.wait_for_navigation(page)
            return True
        except PlaywrightTimeoutError:
            logging.error("Timeout during form submission.")
            return False

    async def navigate_to_report(self, page):
        try:
            reports_xpath = '//*[@id="main-menu"]/li[4]/a'
            reports_a_xpath = '//*[@id="main-menu"]/li[4]/ul/li[2]/a'
            
            await page.wait_for_selector(f'xpath={reports_xpath}')
            await page.click(f'xpath={reports_xpath}')
            logging.info("Report button clicked.")
            
            await page.wait_for_selector(f'xpath={reports_a_xpath}')
            await page.click(f'xpath={reports_a_xpath}')
            logging.info("Sub report button clicked.")
            
            return True
        except PlaywrightTimeoutError:
            logging.error("Navigation failed")
            return False

    async def select_option(self, page, cid):
        try:
            await page.fill('input[id="s2id_autogen3"]', cid)
            await page.wait_for_selector("div.select2-result-label[role='option']")
            await page.click(f"div.select2-result-label[role='option']:has-text('{cid}')")
        except Exception as e:
            logging.error(f"Error selecting option for CID '{cid}': {e}")
            raise

    async def extract_table_headers(self, table):
        try:
            headers = []
            header_elements = await table.query_selector_all('thead th')
            for header in header_elements:
                headers.append(await header.inner_text())
            return headers
        except Exception as e:
            logging.error(f"Error extracting table headers: {e}")
            raise

    async def extract_row_data(self, row, impressions_index, clicks_index, spending_index):
        try:
            columns = await row.query_selector_all('td')
            
            contains_no_results_message = False
            for column in columns:
                div = await column.query_selector('div.empty')
                if div:
                    message = await div.inner_text()
                    if "Your search term did not return any results" in message:
                        contains_no_results_message = True
                        break

            if contains_no_results_message:
                return '0', '0', '0'

            return (
                await columns[impressions_index].inner_text(),
                await columns[clicks_index].inner_text(),
                await columns[spending_index].inner_text()
            )
        except Exception as e:
            logging.error(f"Error extracting row data: {e}")
            raise

    async def extract_footer_data(self, table):
        try:
            footer = await table.query_selector('tfoot')
            if not footer:
                raise Exception("Footer element not found")
            
            tds = await footer.query_selector_all('td')
            if len(tds) < 16:
                raise Exception("Not enough columns in the footer")

            impressions = await tds[2].inner_text()
            clicks = await tds[3].inner_text()
            spending = await tds[15].inner_text()

            logging.info(f"Footer Data - Impressions: {impressions}, Clicks: {clicks}, Spending: {spending}")

            return {
                'Impressions': impressions.strip(),
                'Clicks': clicks.strip(),
                'Spending': spending.strip()
            }
        except Exception as e:
            logging.error(f"Error extracting footer data: {e}")
            return {
                'Impressions': '0',
                'Clicks': '0',
                'Spending': '0'
            }

    async def extract_data_for_creative_id(self, page, cid):
        try:
            await self.select_option(page, cid)
            await page.click("#search-submit-button")
            await page.wait_for_load_state('networkidle')

            table = await page.query_selector('table.table.table-striped.table-light-header')
            header_texts = await self.extract_table_headers(table)
            
            impressions_index = header_texts.index("Impressions")
            clicks_index = header_texts.index("Clicks")
            spending_index = header_texts.index("Spending")
            
            rows = await table.query_selector_all('tbody tr')

            for row in rows:
                impression, clicks, spending = await self.extract_row_data(row, impressions_index, clicks_index, spending_index)
                data = {
                    'creative_id': cid,
                    'Impressions': impression,
                    'Clicks': clicks,
                    'Spending': spending
                }
                return data

            footer_data = await self.extract_footer_data(table)
            footer_data['creative_id'] = cid
            return footer_data
        except Exception as e:
            logging.error(f"Error extracting data for creative ID '{cid}': {e}")
            return {
                'creative_id': cid,
                'Impressions': '0',
                'Clicks': '0',
                'Spending': '0'
            }
        finally:
            # Clear the selection for the next CID
            await page.click("#s2id_detailedstatisticssearch-campaigns > ul > li.select2-search-choice > a")
            # await clear_search_input(page, '#s2id_detailedstatisticssearch-campaigns > ul > li.select2-search-choice > a')

    async def clear_search_input(self, page, clear_button_selector):
        try:
            await page.click(clear_button_selector)
        except Exception as e:
            logging.error(f"Error clearing search input: {e}")
            
    async def extract_table_data(self, page):
        all_data = []
        
        for cid in self.creative_id:
            data = await self.extract_data_for_creative_id(page, cid)
            all_data.append(data)
            
        return all_data

    async def set_yesterdays_date(self, page):
        try:
            await asyncio.sleep(2)
            # Click on the date picker input to open the dropdown
            await page.click("div.kv-drp-dropdown.form-control.daterange.daterange-inline")

            # Wait for the dropdown to be visible
            await page.wait_for_selector("div.daterangepicker.ltr.show-ranges.opensright")
            
            # Select "Yesterday" from the list
            await page.click("li[data-range-key='Yesterday']")

            # Click on the date picker input to open the dropdown for group by
            await page.click("#s2id_detailedstatisticssearch-groupby > a")
            # Wait for the dropdown to be visible
            await page.wait_for_selector("#select2-result-label-21")

            # Select the appropriate group by option
            await page.click("#select2-result-label-21")
            
            await asyncio.sleep(5)
            return True

        except PlaywrightTimeoutError:
            logging.error("Timeout while setting yesterday's date.")
            return False
        except Exception as e:
            logging.error(f"An unexpected error occurred while setting the date: {e}")
            return False