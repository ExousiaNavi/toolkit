import os
import json
import logging
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError
import asyncio

# Configure logging
logging.basicConfig(level=logging.INFO)

class BoAutomation:
    def __init__(self, email: str, password: str, link: str, currency: str, keyword: list[str], max_retries=3):
        self.email = email
        self.password = password
        self.link = link
        self.keyword = keyword # Now expecting a list of strings
        self.currency = currency
        self.max_retries = max_retries
        self.session = None  # Session management for authenticated requests

    async def fill_login_form(self, page):
        try:
            await page.fill('input[name="username"]', self.email)
            await page.fill('input[name="password"]', self.password)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        retries = 0
        while retries < self.max_retries:
            try:
                await page.click('a.submit#login')
                await self.wait_for_navigation(page)
                return True
            except TimeoutError:
                logging.warning(f"Submission failed, retrying {retries + 1}/{self.max_retries}...")
                retries += 1
                await asyncio.sleep(2)
        logging.error("Failed to submit the form after several attempts.")
        return False

    async def wait_for_navigation(self, page):
        try:
            await page.wait_for_load_state('networkidle')
            return True
        except TimeoutError:
            logging.error("Navigation wait timeout.")
            return False

    async def trigger_tab(self, page):
        retries = 0
        while retries < self.max_retries:
            # Define the XPath selector
            menu_selector = '//*[@id="menu140"]/a'
            menu_seclector_2 = '//*[@id="menu143"]/a'
            # # Try clicking the button with force
            # try:
            #     await page.click(f'xpath={menu_selector}', force=True)
            #     logging.info("Search button clicked using force=True.")
            # except PlaywrightTimeoutError:
            #     logging.warning("Failed to click the search button using force.")
            
            # try using JavaScript to click the button
            try:
                await page.eval_on_selector(f'xpath={menu_selector}', "element => element.click()")
                logging.info("Search button clicked using JavaScript.")
            except PlaywrightTimeoutError:
                logging.warning("Failed to click the search button using JavaScript.")
                # Wait for the network to be idle after clicking
                
            await page.wait_for_load_state('networkidle')
            
            # try using JavaScript to click the button
            try:
                await page.eval_on_selector(f'xpath={menu_seclector_2}', "element => element.click()")
                logging.info("Search button clicked using JavaScript.")
            except PlaywrightTimeoutError:
                logging.warning("Failed to click the search button using JavaScript.")
                # Wait for the network to be idle after clicking
                
            await page.wait_for_load_state('networkidle')
            try:
                logging.info("Menu item clicked.")
                await page.click('li#tab1 a[onclick="AffiliatePerformanceHandler.toggleTab(1)"]')
                await page.wait_for_load_state('networkidle')
                return True
            except TimeoutError:
                logging.warning(f"Tab trigger failed, retrying {retries + 1}/{self.max_retries}...")
                retries += 1
                await asyncio.sleep(2)
        logging.error("Failed to trigger tab after several attempts.")
        return False

    async def select_currency(self, page, currency_value):
        retries = 0
        while retries < self.max_retries:
            try:
                await page.select_option('select[name="currencyType"]', value=currency_value)
                await page.wait_for_load_state('networkidle')
                return True
            except TimeoutError:
                logging.warning(f"Currency selection failed, retrying {retries + 1}/{self.max_retries}...")
                retries += 1
                await asyncio.sleep(2)
        logging.error("Failed to select currency after several attempts.")
        return False

    async def select_affiliate(self, page, keywords):
        logging.info(keywords)
        logging.info("Clicking on the Select2 dropdown...")
        await page.click('#s2id_userId')

        all_keywords_selected = True  # Assume success unless proven otherwise

        for keyword in keywords:
            retries = 0
            success = False
            
            while retries < self.max_retries:
                try:
                    # If not successful yet, fill the Select2 dropdown with the keyword
                    if not success:
                        logging.info(f"Filling the Select2 dropdown with keyword '{keyword}'...")
                        await page.fill('.select2-focused', keyword)
                        await page.wait_for_timeout(100)  # Short wait for dropdown to load results

                    # Find all elements that contain the keyword in their text
                    matching_elements = await page.query_selector_all(f'.select2-results li:has-text("{keyword}")')

                    # Iterate over the matching elements to find the exact match
                    for element in matching_elements:
                        # Get the text content of the element
                        text_content = await element.inner_text()
                        print(text_content)

                        # Check if the text content is an exact match with the keyword
                        if text_content.strip() == keyword:
                            # Click on the exact match element
                            await element.click()
                            logging.info(f"Successfully selected the exact match for '{keyword}'")
                            await page.wait_for_load_state('domcontentloaded')
                            success = True
                            break
                    
                    # If an exact match was not found, log an error
                    if not success:
                        logging.error(f"No exact match found for '{keyword}'")

                    # Exit the retry loop if the selection was successful
                    if success:
                        break

                except TimeoutError:
                    logging.warning(f"TimeoutError: Failed to find or select affiliate '{keyword}', retrying {retries + 1}/{self.max_retries}...")
                    retries += 1
                    await asyncio.sleep(2)
                except Exception as e:
                    logging.error(f"Error while trying to select affiliate '{keyword}': {e}")
                    retries += 1
                    await asyncio.sleep(2)

            if not success:
                logging.error(f"Failed to select affiliate '{keyword}' after {self.max_retries} attempts.")
                all_keywords_selected = False

        return all_keywords_selected


    async def scroll_to_bottom(self, page):
        await page.evaluate("window.scrollTo(0, document.body.scrollHeight)")
        await page.wait_for_timeout(2000)  # Wait for dynamic content to load after scrolling

    async def get_yesterday_date(self):
        yesterday = datetime.now() - timedelta(days=1)
        return yesterday.year, yesterday.month, yesterday.day

    async def set_date(self, page, year, month, day):
        date_string = f"{year}/{month:02d}/{day:02d}"
        input_selectors = ['#searchTimeStart', '#searchTimeEnd']
        
        # Fill the date fields
        for selector in input_selectors:
            await page.fill(selector, date_string)
        
        # Define the XPath selector for the search button
        searchBtn_xpath = '//*[@id="content"]/div/div[3]/div/div/div/div/div/div[2]/form/div[6]/div/div[2]/div/input'
        
        # Wait for the search button to be visible using XPath
        await page.wait_for_selector(f'xpath={searchBtn_xpath}', state='visible')
        
        # Try clicking the button with force
        try:
            await page.click(f'xpath={searchBtn_xpath}', force=True)
            logging.info("Search button clicked using force=True.")
        except PlaywrightTimeoutError:
            logging.warning("Failed to click the search button using force.")
        
        # Alternatively, try using JavaScript to click the button
        try:
            await page.eval_on_selector(f'xpath={searchBtn_xpath}', "element => element.click()")
            logging.info("Search button clicked using JavaScript.")
        except PlaywrightTimeoutError:
            logging.warning("Failed to click the search button using JavaScript.")
            # Wait for the network to be idle after clicking
            
        await page.wait_for_load_state('networkidle')
        
        # Check for div visibility and data availability
        try:
            div_selector = '#dataDiv'  # Replace with the actual selector
            await page.wait_for_selector(div_selector, state='visible', timeout=5000)
            logging.info("The div is visible.")
            
            # Optionally, you can check if data is present in the div
            div_content = await page.inner_text(div_selector)
            if div_content.strip():
                logging.info("Data is displayed.")
            else:
                logging.warning("No data is displayed.")
        except PlaywrightTimeoutError:
            logging.warning("The div is not visible or does not have data displayed.")
        
        return True
    
    async def click_search(self, page):
    
        searchBtn_selector = 'input[value="Search"]'
    
        # Wait for the search button to be visible and get the element handle
        searchBtn = await page.wait_for_selector(searchBtn_selector, state='visible', timeout=5000)
        
        # Click the search button
        await searchBtn.click()
        logging.info("Search Clicked.")
        await page.wait_for_timeout(2000)
        
        
        return True
    
    async def check_div_visibility(self, page, target):
        await page.wait_for_load_state('domcontentloaded')
        is_visible = await page.is_visible(target)
        if is_visible:
            logging.info("The div is visible and has data displayed.")
            
            entriesBtn = '#s2id_autogen6 .select2-choice'
            entries_max = '//ul[@class="select2-results"]/li[4]'

            try:
                # Wait for the entries button to be available before interacting
                await page.wait_for_selector(entriesBtn, timeout=5000)
                
                # Click the entries button to open the dropdown
                await page.click(entriesBtn)
                logging.info("Entries dropdown opened successfully.")

                # Wait for the entries dropdown to appear
                await page.wait_for_selector(entries_max, timeout=5000)
                
                # Click the maximum entries option
                await page.click(entries_max)
                logging.info("Max entries selected successfully.")

            except PlaywrightTimeoutError:
                logging.warning("Failed to click the menu button or max entries option.")
            except Exception as e:
                logging.error(f"An error occurred during the automation process: {str(e)}")

            # Wait for the network to be idle after clicking
            await page.wait_for_load_state('networkidle')
            await asyncio.sleep(2)
        else:
            logging.warning("The div is not visible or does not have data displayed.")
        return is_visible

    # async def save_data_to_json(self, data, file_path):
    #     # Ensure the directory exists
    #     os.makedirs(os.path.dirname(file_path), exist_ok=True)
        
    #     with open(file_path, 'w') as f:
    #         json.dump(data, f, indent=4)

    # async def extract_table_data(self, page):
    #     header_selector = '#performanceAffiliateTable thead tr th'
        
    #     # Fetching the headers
    #     header_elements = await page.query_selector_all(header_selector)
    #     headers = [await th.inner_text() for th in header_elements]  # Await inner_text
    #     headers = [header.strip() for header in headers]  # Strip any whitespace

    #     tbody_selector = '#performanceAffiliateTable tbody[role="alert"]'
    #     rows = await page.query_selector_all(f'{tbody_selector} tr')
        
    #     table_data = []
    #     affiliate_username = None
        
    #     for row in rows:
    #         cells = await row.query_selector_all('td')
    #         row_data = [await cell.inner_text() for cell in cells]  # Await inner_text
            
    #         # Ensure we are stripping any whitespace from the cells
    #         row_data = [data.strip() for data in row_data]

    #         # Get the affiliate username from the specified index
    #         if not affiliate_username and len(row_data) > 2:
    #             affiliate_username = row_data[2]  # Adjust this index based on where the username is located

    #         # Only add row data if it matches the header length
    #         if len(row_data) == len(headers):
    #             row_dict = dict(zip(headers, row_data))
    #             table_data.append(row_dict)
    #         else:
    #             logging.warning(f"Row data length {len(row_data)} does not match headers length {len(headers)}. Skipping this row.")

    #     return table_data, affiliate_username


    async def extract_table_data(self, page):
        # Currency mapping
        currency_mapping = {
            '-1': 'all',
            '8': 'BDT',
            '2': 'VND',
            '15': 'USD',
            '7': 'INR',
            '17': 'PKR',
            '16': 'PHP',
            '5': 'KRW',
            '6': 'IDR',
            '24': 'NPR',
            '9': 'THB',
        }
        header_selector = '#performanceAffiliateTable thead tr th'
        
        # Fetching the headers
        header_elements = await page.query_selector_all(header_selector)
        headers = [await th.inner_text() for th in header_elements]
        headers = [header.strip() for header in headers]

        tbody_selector = '#performanceAffiliateTable tbody[role="alert"]'
        rows = await page.query_selector_all(f'{tbody_selector} tr')
        
        table_data = []
        found_usernames = set()  # To track which usernames were found

        for row in rows:
            cells = await row.query_selector_all('td')
            row_data = [await cell.inner_text() for cell in cells]
            row_data = [data.strip() for data in row_data]

            # Skip rows that contain "No data available in table"
            if len(row_data) == 1 and "No data available in table" in row_data[0]:
                logging.info("No valid rows found in the table. Skipping.")
                continue
            
            # Get the affiliate username from the specified index
            if len(row_data) > 2:
                affiliate_username = row_data[2]  # Adjust this index if needed
                found_usernames.add(affiliate_username)

                # Only add row data if it matches the header length
                if len(row_data) == len(headers):
                    row_dict = dict(zip(headers, row_data))
                    table_data.append(row_dict)
                else:
                    continue  # Skip the incomplete row

        # Handle missing keywords
        missing_keywords = set(self.keyword) - found_usernames
        for missing_keyword in missing_keywords:
            custom_row = dict(zip(headers, ['0'] * len(headers)))
            custom_row['Affiliate Username'] = missing_keyword
            custom_row['Currency'] = currency_mapping[self.currency]  # Apply currency mapping
            table_data.append(custom_row)


        return table_data #list(found_usernames)


    async def fetch_bo(self):
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
                if not await self.trigger_tab(page):
                    return {"status": 400, "text": "Failed to navigate after login."}
                if not await self.select_currency(page, self.currency):
                    return {"status": 400, "text": "Failed to select currency."}
                
                keyword_list = self.keyword
                if not await self.select_affiliate(page, keyword_list):
                    return {"status": 400, "text": "Failed to select affiliate."}
                
                if not await self.set_date(page, year, month, day):
                    return {"status":400, "text": "Failed to set date."}
                
                # if not await self.click_search(page):
                #     return {"status":400, "text": "Failed to click search."}
             
                # await self.scroll_to_bottom(page)
                    
                
                if await self.check_div_visibility(page, '#affiliateDiv'):
                    logging.info("Data is displayed.")
                    table_data = await self.extract_table_data(page)
                    print(table_data)
                    # affiliate_username = affiliate_username or 'default_filename'  # Fallback filename
                    # file_path = f'json/{affiliate_username}_table_data.json'
                    # await self.save_data_to_json(table_data, file_path)
                    data = {
                        "status": 200,
                        "text": "Automation for BO and FE data has been collected...",
                        "title": "Automation Completed!",
                        "icon": "success",
                        "bo": table_data,
                    }
                else:
                    logging.warning("No data is displayed.")
                
                await page.screenshot(path='screenshot.png')
            except Exception as e:
                logging.error(f"An error occurred during the automation process: {e}")
            finally:
                await browser.close()
                return data

    async def fetch_data(self):
        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=False)
            page = await browser.new_page()
            await page.goto("https://laravel.com/docs/11.x/migrations#foreign-key-constraints")
            title = await page.title()
            await browser.close()
            # return {"title": title}
        data = {
            "status": 200,
            "text": "Data Fetched successfully",
            "title": "Fetch Completed!",
            "icon": "success",
            "data": [title],
        }
        return data
    
    # async def test(self):
    #      async with async_playwright() as p:
    #         try:
                
    #             browser = await p.chromium.launch(headless=False)
    #             page = await browser.new_page()
    #             await page.goto("https://laravel.com/docs/11.x/migrations#foreign-key-constraints")
    #             pass
    #         except Exception as e:
    #             return {"error": str(e)}, 400
