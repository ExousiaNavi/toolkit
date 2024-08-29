import asyncio
import logging
import os
import json
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
        try:
            # Load session if it exists
            if Path(SESSION_FILE).exists():
                state = json.loads(Path(SESSION_FILE).read_text())
                browser = await p.chromium.launch(headless=False)
                context = await browser.new_context(storage_state=state)
                page = await context.new_page()
                await page.goto('https://adcash.myadcash.com/dashboard/main')
                logging.info("Loaded existing session.")
            else:
                browser = await p.chromium.launch(headless=False)
                context = await browser.new_context()
                page = await context.new_page()
                await page.goto(link)
                
                # Execute functions in a try-except block to handle potential errors
                if not await fill_login_form(page, email, password):
                    return {"status": 400, "text": "Failed to fill the login form."}
                    
                if not await submit_form(page):
                    return {"status": 400, "text": "Failed to submit the login form."}
                
             #proceed       
            if not await navigate_to_report(page):
                    return {"status": 400, "text": "Failed to navigate to the report page."}
                            
            if not await set_yesterdays_date(page):
                return {"status": 400, "text": "Failed to set yesterday's date."}
                    
            # Call the function to extract the data
            table_data = await extract_table_data(page, creative_id)
            print(table_data)
                
            # Save the session after a successful login
            state = await context.storage_state()
            Path(SESSION_FILE).write_text(json.dumps(state))
            logging.info("Session saved.")
                
        except Exception as e:
            logging.error(f"[ERROR] An unexpected error occurred: {e}")
        finally:
            await browser.close()
            
        await asyncio.sleep(5)

async def wait_for_navigation(page):
    try:
        await page.wait_for_load_state('networkidle')
        return True
    except PlaywrightTimeoutError:
        logging.error("Navigation wait timeout.")
        return False

async def fill_login_form(page, email, password):
    try:
        await page.click('body > header > div > nav.button-menu > a:nth-child(1)')
        await page.fill('input[name="username"]', email)
        await page.fill('input[name="password"]', password)
        return True
    except Exception as e:
        logging.error(f"Error filling login form: {e}")
        return False

async def submit_form(page):
    try:
        await page.click('#kc-login')
        await wait_for_navigation(page)
        return True
    except PlaywrightTimeoutError:
        logging.error("Timeout during form submission.")
        return False

async def navigate_to_report(page):
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

async def clear_search_input(page, clear_button_selector):
    try:
        await page.click(clear_button_selector)  # Click the clear button if applicable
    except Exception as e:
        logging.error(f"Error clearing search input: {e}")

async def select_option(page, cid):
    try:
        await page.fill('input[id="s2id_autogen3"]', cid)
        await page.wait_for_selector("div.select2-result-label[role='option']")
        await page.click(f"div.select2-result-label[role='option']:has-text('{cid}')")
    except Exception as e:
        logging.error(f"Error selecting option for CID '{cid}': {e}")
        raise

async def extract_table_headers(table):
    try:
        headers = []
        header_elements = await table.query_selector_all('thead th')
        for header in header_elements:
            headers.append(await header.inner_text())
        return headers
    except Exception as e:
        logging.error(f"Error extracting table headers: {e}")
        raise

async def extract_row_data(row, impressions_index, clicks_index, spending_index):
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

async def extract_footer_data(table):
    try:
        footer = await table.query_selector('tfoot')
        if not footer:
            raise Exception("Footer element not found")
        
        tds = await footer.query_selector_all('td')
        if len(tds) < 16:  # Ensure there are enough columns
            raise Exception("Not enough columns in the footer")

        # Extracting values from the footer
        impressions = await tds[2].inner_text()
        clicks = await tds[3].inner_text()
        spending = await tds[15].inner_text()

        # Log extracted values
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
        }  # Default values

async def extract_data_for_creative_id(page, cid):
    try:
        await select_option(page, cid)
        await page.click("#search-submit-button")
        
        # Wait for the page to load the results after clicking the search button
        await page.wait_for_load_state('networkidle')  # Wait until the network is idle after the button click

         
        table = await page.query_selector('table.table.table-striped.table-light-header')
        header_texts = await extract_table_headers(table)
        
        impressions_index = header_texts.index("Impressions")
        clicks_index = header_texts.index("Clicks")
        spending_index = header_texts.index("Spending")
        
        rows = await table.query_selector_all('tbody tr')

        # Extract data for each row in the table
        for row in rows:
            impression, clicks, spending = await extract_row_data(row, impressions_index, clicks_index, spending_index)
            data = {
                'creative_id': cid,
                'Impressions': impression,
                'Clicks': clicks,
                'Spending': spending
            }
            return data  # Return the data for the current `creative_id`
        
        # If you want to also fetch footer data
        footer_data = await extract_footer_data(table)
        return {
            'creative_id': cid,
            **footer_data  # Merge footer data with the creative ID data
        }

    except Exception as e:
        logging.error(f"Error processing CID '{cid}': {e}")
        return {
            'creative_id': cid,
            'Impressions': '0',
            'Clicks': '0',
            'Spending': '0'
        }  # Return default values in case of an error

    finally:
        # Clear the selection for the next CID
        await clear_search_input(page, '#s2id_detailedstatisticssearch-campaigns > ul > li.select2-search-choice > a')

async def extract_table_data(page, creative_ids):
    all_data = []
    
    for cid in creative_ids:
        data = await extract_data_for_creative_id(page, cid)
        all_data.append(data)
        # await asyncio.sleep(10)
    return all_data

async def set_yesterdays_date(page):
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

if __name__ == "__main__":
    asyncio.run(main(
        keywords="adcashpkr",
        email="abiralmilan1014@gmail.com",
        password="B@j!qwe@4444",
        link="https://auth.myadcash.com/",
        creative_id=['385568820', '390697020', '390697620']
    ))
