import asyncio
import os
from pathlib import Path
import logging
import json
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError

class ExoclickAutomation:
    def __init__(self, keywords, email, password, link, creative_id, dashboard, platform):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
        self.session_dir = "sessions"
        self.session_file_name = f"{keywords.replace(' ', '_')}_{datetime.now().strftime('%Y-%m-%d')}.json"
        self.session_file_path = os.path.join(self.session_dir, self.session_file_name)

        logging.basicConfig(level=logging.INFO)

    def get_yesterday_session_file(self):
        yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
        session_file_name = f"{self.keywords.replace(' ', '_')}_{yesterday}.json"
        return os.path.join(self.session_dir, session_file_name)

    def delete_yesterdays_session(self):
        yesterday_session_file = self.get_yesterday_session_file()
        if Path(yesterday_session_file).exists():
            os.remove(yesterday_session_file)
            logging.info(f"Deleted yesterday's session file: {yesterday_session_file}")

    async def run(self):
        async with async_playwright() as p:
            while True:
                try:
                    os.makedirs(self.session_dir, exist_ok=True)
                    self.delete_yesterdays_session()

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
                        
                        if not await self.fill_login_form(page):
                            return {"status": 400, "text": "Failed to fill the login form."}
                        
                        if not await self.submit_form(page):
                            return {"status": 400, "text": "Failed to submit the login form."}
                    
                    await page.wait_for_load_state('load')
                    
                    if not await self.report(page):
                        return {"status": 400, "text": "Failed to click report button."}
                    await page.wait_for_load_state('load')
                
                    if not await self.scrapping(page):
                        return {"status": 400, "text": "Failed to set yesterday's date."}
                    await page.wait_for_load_state('load')
                    await asyncio.sleep(5)

                    state = await context.storage_state()
                    Path(self.session_file_path).write_text(json.dumps(state))
                    logging.info("Session saved.")
                    break  # Exit the loop if successful
                
                except Exception as e:
                    print(f"[ERROR] An unexpected error occurred: {e}")
                finally:
                    await context.close()
                    await browser.close()

    async def fill_login_form(self, page):
        try:
            # await page.get_by_role("banner").get_by_role("link", name="Log in").click()
            # await page.fill('input[name="email"]', self.email)
            # await page.fill('input[name="password"]', self.password)
            await page.get_by_placeholder("Username").click()
            await page.get_by_placeholder("Username").fill(self.email)
            await page.get_by_placeholder("Password").click()
            await page.get_by_placeholder("Password").fill(self.password)
            # await page.mouse.wheel(0, 1000)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        try:
            await page.get_by_role("button", name="Log In").click()
            await self.wait_for_navigation(page)
            return True
        except PlaywrightTimeoutError:
            logging.error("Timeout during form submission.")
            return False

    async def wait_for_navigation(self, page):
        try:
            await page.wait_for_load_state('networkidle')
            return True
        except PlaywrightTimeoutError:
            logging.error("Navigation wait timeout.")
            return False

    async def report(self, page):
        try:
            # reports = '//*[@id="navleftrowinitial"]/div[4]/a'
            try:
                await page.get_by_role("tab", name="Campaigns", exact=True).click()
                logging.info("Report button clicked using JavaScript.")
            except PlaywrightTimeoutError:
                logging.warning("Failed to click the report button using JavaScript.")
            await page.wait_for_load_state('networkidle')
            await asyncio.sleep(2)
            return True
        except PlaywrightTimeoutError:
            logging.error("Navigation failed")
            return False

    async def scrapping(self, page):
        try:
            await asyncio.sleep(5)
            await page.locator(".mat-mdc-form-field-infix").first.click()
            await page.get_by_text("Yesterday").click()
            await asyncio.sleep(2)
            
            results = []
            for cid in self.creative_id:
                await page.get_by_placeholder("Name, ID, Type").fill("")
                await asyncio.sleep(2)
                await page.get_by_placeholder("Name, ID, Type").fill(cid)
                await asyncio.sleep(2)
                await page.get_by_role("button", name="Apply").click()
                
                await asyncio.sleep(5)
            
                # Initialize default values
                imprs = '0'
                clcks = '0'
                cost = '0'

                try:
                    # Attempt to extract impressions using XPath
                    imprs = await page.locator('xpath=//*[@id="campaigns-list-view-table"]/div/div[2]/ag-grid-angular/div/div[1]/div[2]/div[5]/div[2]/div/div/div[6]/div/span').inner_text()
                except Exception as e:
                    print(f"Could not find impressions for creative_id {cid}: {e}")

                try:
                    # Attempt to extract clicks using XPath
                    clcks = await page.locator('xpath=//*[@id="campaigns-list-view-table"]/div/div[2]/ag-grid-angular/div/div[1]/div[2]/div[5]/div[2]/div/div/div[7]/div/span').inner_text()
                except Exception as e:
                    print(f"Could not find clicks for creative_id {cid}: {e}")

                try:
                    # Attempt to extract cost using XPath
                    cost = await page.locator('xpath=//*[@id="campaigns-list-view-table"]/div/div[2]/ag-grid-angular/div/div[1]/div[2]/div[5]/div[2]/div/div/div[9]/div/span').inner_text()
                except Exception as e:
                    print(f"Could not find cost for creative_id {cid}: {e}")

                # Prepare the data for this creative ID
                data = {
                    'creative_id': cid,
                    'Impressions': imprs,
                    'Clicks': clcks,
                    'Spending': cost,
                }
                results.append(data)
                logging.info(f"Data for creative_id '{cid}' collected: {data}")
            
            logging.info(f"Collected: {results}")
            return results

        except Exception as e:
            logging.error(f"An error occurred while scraping data: {str(e)}")
            return None
        except PlaywrightTimeoutError:
            logging.error("Input yesterday wait timeout.")
            return False

    # async def fetch_info(self):
    #     await self.run()

# Example usage in another file
# if __name__ == "__main__":
#     automation = ExoclickAutomation(
#         keywords="exoclick",
#         email="changchee",
#         password="B@j!09876**1",
#         link="https://admin.exoclick.com/login",
#         creative_id=['6394024','6705106','67051061'],
#         dashboard="https://admin.exoclick.com/panel/advertiser/dashboard",
#         platform="exoclick"
#     )
#     asyncio.run(automation.fetch_info())
