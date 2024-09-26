import os
import json
import logging
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError
import asyncio

# Configure logging
logging.basicConfig(level=logging.INFO)

class FeAutomation:
    def __init__(self, username: str, password: str, link: str, currency: str,targetdate: str, max_retries=3):
        self.username = username
        self.password = password
        self.link = link
        self.currency = currency
        self.targetdate = targetdate
        self.max_retries = max_retries
        self.session = None  # Session management for authenticated requests

    # get the date yesterday
    async def get_yesterday_date(self):
        yesterday = datetime.now() - timedelta(days=1)
        return yesterday.year, yesterday.month, yesterday.day
    
    #wait the page load
    async def wait_for_navigation(self, page):
        try:
            await page.wait_for_load_state('networkidle')
            return True
        except TimeoutError:
            logging.error("Navigation wait timeout.")
            return False
        
    # authenticate
    async def fill_login_form(self, page):
        try:
            await page.fill('input[name="userId"]', self.username)
            await page.fill('input[name="password"]', self.password)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False
    
    #submit the form login
    async def submit_form(self, page):
        retries = 0
        while retries < self.max_retries:
            try:
                await page.click('button#login')
                await self.wait_for_navigation(page)
                return True
            except TimeoutError:
                logging.warning(f"Submission failed, retrying {retries + 1}/{self.max_retries}...")
                retries += 1
                await asyncio.sleep(2)
        logging.error("Failed to submit the form after several attempts.")
        return False
       
    async def trigger_sidebar(self, page):
        retries = 0
        while retries < self.max_retries:
            menu_selector = '//*[@id="menu_4"]'
            menu_selector_nsu = '//*[@id="menu_7"]'
            await asyncio.sleep(2)
            try:
                # Try using JavaScript to click the first menu button
                await page.eval_on_selector(f'xpath={menu_selector}', "element => element.click()")
                logging.info("Search button clicked using JavaScript.")
            except PlaywrightTimeoutError:
                logging.warning("Failed to click the menu button using JavaScript.")
            
            # Wait for the network to be idle after clicking
            await page.wait_for_load_state('networkidle')
            
            try:
                # Try using JavaScript to click the second menu button
                await page.eval_on_selector(f'xpath={menu_selector_nsu}', "element => element.click()")
                logging.info("NSU button clicked using JavaScript.")
            except PlaywrightTimeoutError:
                logging.warning("Failed to click the NSU button using JavaScript.")
            
            # Wait for the network to be idle after clicking
            await page.wait_for_load_state('networkidle')
            
            try:
                logging.info("Menu item clicked.")
                # await page.click('li#tab1 a[onclick="AffiliatePerformanceHandler.toggleTab(1)"]')
                await page.wait_for_load_state('networkidle')
                await asyncio.sleep(2)
                return True
            except TimeoutError:
                logging.warning(f"Tab trigger failed, retrying {retries + 1}/{self.max_retries}...")
                retries += 1
                await asyncio.sleep(2)
        
        # If the loop finishes without returning early, log an error
        logging.error("Failed to trigger sidebar after several attempts.")
        return False

    
    #start searching
    async def search_data(self, page):
        retries = 0
        while retries < self.max_retries:
            input_date = '//*[@id="registrationsForm"]/div/div[2]/div[2]/div[1]/input'
            date_btn = '//*[@id="dateFilter"]/button'
            yesterday_btn = '//*[@id="dateFilter"]/div/dl/dd/div/div/button[2]'
            # entries_btn = '//*[@id="registrationsTable_length"]/label/select'
            # entries_value = '//*[@id="registrationsTable_length"]/label/select/option[4]'
            # try using JavaScript to click the button
            # try:
            #     await page.eval_on_selector(f'xpath={date_btn}', "element => element.click()")
            #     logging.info("date button clicked using JavaScript.")
            # except PlaywrightTimeoutError:
            #     logging.warning("Failed to click the menu button using JavaScript.")
            #     # Wait for the network to be idle after clicking
                
            # await page.wait_for_load_state('networkidle')
            # await asyncio.sleep(2)
            
            try:
                # await page.eval_on_selector(f'xpath={yesterday_btn}', "element => element.click()")
                originaldate = self.targetdate
                formatted_date = originaldate.replace('/','-')
                await page.fill("//*[@id=\"registrationsForm\"]/div/div[2]/div[2]/div[1]/input", formatted_date)
                await asyncio.sleep(2)
                await page.fill("//*[@id=\"registrationsForm\"]/div/div[2]/div[2]/div[2]/input", formatted_date)
                logging.info("filled successfully for input date.")
            except PlaywrightTimeoutError:
                logging.warning("Failed to click the menu button using JavaScript.")
                # Wait for the network to be idle after clicking
                
            await page.wait_for_load_state('networkidle')
            await asyncio.sleep(2)
            
            # Assuming 'registrationsTable_length' is the name attribute of the select element
            await page.select_option('select[name="registrationsTable_length"]', value="100")
            logging.info("Selected 100 entries from the dropdown.")
            
            
            try:
                logging.info("yesterday button was click.")
                # await page.click('li#tab1 a[onclick="AffiliatePerformanceHandler.toggleTab(1)"]')
                await page.wait_for_load_state('networkidle')
                await asyncio.sleep(2)
                return True
            except TimeoutError:
                logging.warning(f"Tab trigger failed, retrying {retries + 1}/{self.max_retries}...")
                retries += 1
                await asyncio.sleep(2)
        logging.error("Failed to trigger tab after several attempts.")
        return False
            
    async def extract_table_data(self, page):
        header_selector = '#registrationsTable thead tr th'
        
        # Fetching the headers
        header_elements = await page.query_selector_all(header_selector)
        headers = [await th.inner_text() for th in header_elements]
        headers = [header.strip() for header in headers]
        
        logging.info(f"Headers found: {headers}")

        tbody_selector = '#registrationsTable tbody'
        rows = await page.query_selector_all(f'{tbody_selector} tr')
        
        table_data = []

        valid_row_found = False
        
        for row in rows:
            cells = await row.query_selector_all('td')
            row_data = [await cell.inner_text() for cell in cells]
            row_data = [data.strip() for data in row_data]
            
            logging.info(f"Row data value: {row_data}")

            # Skip rows that contain "No data available in table"
            if len(row_data) == 1 and "No data available in table" in row_data[0]:
                logging.info("No valid rows found in the table. Skipping.")
                continue

            # Only add row data if it matches the header length
            if len(row_data) == len(headers):
                row_dict = dict(zip(headers, row_data))
                table_data.append(row_dict)
                valid_row_found = True
            else:
                logging.warning(f"Row skipped due to length mismatch: {row_data}")
                continue

        # If no valid rows were found, add a zeroed row
        if not valid_row_found:
            logging.info("No valid rows found after processing. Adding a zeroed row.")
            # custom_row = {header: '0' for header in headers}
            custom_row = dict(zip(headers, ['0'] * len(headers)))
            # custom_row['Affiliate Username'] = missing_keyword
            table_data.append(custom_row)
            logging.info(table_data)
        
        
        #table data completed now get the ftd
        fdt_btn = '//*[@id="menu_8"]'
        fdt_date_btn = '//*[@id="dateFilter"]/button'
        fdt_yesterday = '//*[@id="dateFilter"]/div/dl/dd/div/div/button[2]'
        # ftd_select = '//*[@id="performanceTable_length"]/label/select'
        
        try:
            # Try using JavaScript to click the second menu button
            await page.eval_on_selector(f'xpath={fdt_btn}', "element => element.click()")
            logging.info("NSU button clicked using JavaScript.")
        except PlaywrightTimeoutError:
            logging.warning("Failed to click the NSU button using JavaScript.")
            # Wait for the network to be idle after clicking
        await page.wait_for_load_state('networkidle')
        await asyncio.sleep(2)
        
        # try:
        #     # Try using JavaScript to click the second menu button
        #     await page.eval_on_selector(f'xpath={fdt_date_btn}', "element => element.click()")
        #     logging.info("NSU button clicked using JavaScript.")
        # except PlaywrightTimeoutError:
        #     logging.warning("Failed to click the NSU button using JavaScript.")
        #     # Wait for the network to be idle after clicking
        # await page.wait_for_load_state('networkidle')
        # await asyncio.sleep(2)
        
        # try:
        #     # Try using JavaScript to click the second menu button
        #     await page.eval_on_selector(f'xpath={fdt_yesterday}', "element => element.click()")
        #     logging.info("NSU button clicked using JavaScript.")
        # except PlaywrightTimeoutError:
        #     logging.warning("Failed to click the NSU button using JavaScript.")
        #     # Wait for the network to be idle after clicking
        # await page.wait_for_load_state('networkidle')
        
        originaldate = self.targetdate
        formatted_date = originaldate.replace('/','-')
        await page.fill("//*[@id=\"performanceForm\"]/div[3]/div[1]/input", formatted_date)
        await asyncio.sleep(2)
        await page.fill("//*[@id=\"performanceForm\"]/div[3]/div[2]/input", formatted_date)
        logging.info("filled successfully for input date.")
                
        await asyncio.sleep(2)
            
        # Assuming 'registrationsTable_length' is the name attribute of the select element
        await page.select_option('select[name="performanceTable_length"]', value="100")
        logging.info("Selected 100 entries from the dropdown.")
            
        header_selector_ftd = '#performanceTable thead tr th'
        
        # Fetching the headers
        header_elements_ftd = await page.query_selector_all(header_selector_ftd)
        headers_ftd = [await th.inner_text() for th in header_elements_ftd]
        headers_ftd = [header_ftd.strip() for header_ftd in headers_ftd]
        
        logging.info(f"Headers found: {headers_ftd}")

        tbody_selector = '#performanceTable tbody'
        rows_ftd = await page.query_selector_all(f'{tbody_selector} tr')
        
        table_data_ftd = []

        valid_row_found_ftd = False
        for row_ftd in rows_ftd:
            cells_ftd = await row_ftd.query_selector_all('td')
            row_data_ftd = [await cell_ftd.inner_text() for cell_ftd in cells_ftd]
            row_data_ftd = [data_ftd.strip() for data_ftd in row_data_ftd]
            
            logging.info(f"Row data value: {row_data_ftd}")

            # Skip rows that contain "No data available in table"
            if len(row_data_ftd) == 1 and "No data available in table" in row_data_ftd[0]:
                logging.info("No valid rows found in the table. Skipping.")
                continue

            # Only add row data if it matches the header length
            if len(row_data_ftd) == len(headers_ftd):
                row_dict_ftd = dict(zip(headers_ftd, row_data_ftd))
                table_data_ftd.append(row_dict_ftd)
                valid_row_found_ftd = True
            else:
                logging.warning(f"Row skipped due to length mismatch: {row_data_ftd}")
                continue
        # If no valid rows were found, add a zeroed row
        if not valid_row_found_ftd:
            logging.info("No valid rows found after processing. Adding a zeroed row.")
            # custom_row = {header: '0' for header in headers}
            custom_row_ftd = dict(zip(headers_ftd, ['0'] * len(headers_ftd)))
            # custom_row['Affiliate Username'] = missing_keyword
            table_data_ftd.append(custom_row_ftd)
            logging.info(table_data_ftd)
            
        return table_data , table_data_ftd # Returning only table_data











    async def fetch_fe(self):
        year, month, day = await self.get_yesterday_date()
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=False)
            page = await browser.new_page()
            data = {}
            try:
                
                await page.goto(self.link)
                if not await self.fill_login_form(page):
                    return {"status": 400, "text": "Failed to fill the login form."}
                if not await self.submit_form(page):
                    return {"status": 400, "text": "Failed to submit the login form."}
                if not await self.trigger_sidebar(page):
                    return {"status": 400, "text": "Failed to navigate after login."}
                if not await self.search_data(page):
                    return {"status": 400, "text": "Failed to navigate after login."}
                
                table_data, table_data_ftd = await self.extract_table_data(page)
                print(table_data_ftd)
                data = {
                        "status": 200,
                        "text": "Data Fetched successfully",
                        "title": "Fetch Completed!",
                        "icon": "success",
                        "fe": table_data,
                        "ftd" : table_data_ftd,
                    }
            except Exception as e:
                logging.error(f"An error occurred during the automation process: {e}")
            finally:
                await browser.close()
                return data
        