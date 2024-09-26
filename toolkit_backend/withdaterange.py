import asyncio
from datetime import datetime, timedelta
from playwright.async_api import async_playwright

# Function to locate, print, and click the matching `td` elements under the calendar
async def extract_td_elements(page, header_tbody_xpath, previous_days):
    
    print(f"Target dates to click: {previous_days}")

    # Locate all matching `td` elements in the calendar
    td_elements = await page.locator(header_tbody_xpath).all()

    if not td_elements:
        print("No `td` elements found in the calendar. Check the XPath or page state.")
    else:
        # Loop through each `td` element and check if it matches any of the target days
        for td in td_elements:
            td_text = await td.inner_text()  # Get the text content of the `td` element
            td_text = td_text.strip()  # Clean up any surrounding whitespace
            # Check if the text matches any of the previous three days
            if td_text == previous_days:
                print(f"Clicking on day {td_text}")
                await td.dblclick()
                await asyncio.sleep(3)
                
                #click the button apply
                await page.click("//div[@class='drp-buttons']//button[contains(@class, 'applyBtn')]")
                await asyncio.sleep(3)

# Function to locate and check the month headers in both calendars
async def dateHeader(page):
    # Define XPaths for selecting the left and right date headers
    calendar_paths = {
        "left": "//div[@class='drp-calendar left']//th[@class='month']",
        "right": "//div[@class='drp-calendar right']//th[@class='month']"
    }

    # Get the current month and year in the format 'Sep 2024'
    current_month_year = datetime.now().strftime("%b %Y")

    # Loop through the left and right calendars
    for position, xpath in calendar_paths.items():
        # Locate the calendar header
        headers = await page.locator(xpath).all()

        if not headers:
            print(f"No {position} calendar headers found. Check the XPath or page state.")
        else:
            for header in headers:
                header_text = await header.inner_text()
                print(f"{position.capitalize()} Calendar Month:", header_text)

                # Check if the header matches the current month and year
                if current_month_year == header_text.strip():
                    print(f"{position.capitalize()} formatted date: {header_text}")
                    # Define the `td` elements XPath dynamically based on the calendar position
                    header_tbody_xpath = f"//div[@class='drp-calendar {position}']//td"
                    # await extract_td_elements(page, header_tbody_xpath, tday)
                    return header_tbody_xpath

# Main Playwright script
async def main():
    async with async_playwright() as p:
        # Launch browser in non-headless mode (headless=False to see the browser actions)
        browser = await p.chromium.launch(headless=False)
        context = await browser.new_context()
        page = await context.new_page()
        
        # Navigate to the URL
        await page.goto("https://auth.myadcash.com/")
        await page.get_by_role("banner").get_by_role("link", name="Sign in").click()

        # Fill in email and password and log in
        await page.get_by_placeholder("E-mail").click()
        await page.get_by_placeholder("E-mail").fill("bo.cc@chengyi-1.com")
        await page.get_by_placeholder("Password").click()
        await page.get_by_placeholder("Password").fill("B@j!09876**1")
        await page.get_by_role("button", name="Log In Arrow right").click()

        # Navigate to the "Reports" section and then "Detailed statistics"
        await page.get_by_role("link", name="  Reports").click()
        await page.get_by_role("link", name="Detailed statistics").click()

        await asyncio.sleep(2)
        
        
            
        # Get today's date
        today = datetime.now()
        
        # Get the previous three days (formatted as two digits)
        # previous_days = [(today - timedelta(days=i)).strftime("%d") for i in range(1, 4)]
        # Check if today is Monday (Monday is represented by 0)
        if today.weekday() == 0:
            # If today is Monday, get the previous 4 days
            previous_days = [(today - timedelta(days=i)).strftime("%d") for i in range(1, 4)]
            print("Today is Monday. Processing the last 4 days:", previous_days)
        else:
            # If today is not Monday, get only yesterday
            previous_days = [(today - timedelta(days=1)).strftime("%d")]
            print("Today is not Monday. Processing only yesterday:", previous_days)
            
        for day in previous_days:
            # Click on the date picker input to open the dropdown
            await page.click("div.kv-drp-dropdown.form-control.daterange.daterange-inline")

            # Wait for the dropdown to be visible
            await page.wait_for_selector("div.daterangepicker.ltr.show-ranges.opensright")

            # Select "Custom Range" from the list
            await page.click("li[data-range-key='Custom Range']")

            # Call the function to extract and check date headers
            await asyncio.sleep(2)
                
            xpath = await dateHeader(page)
        
            await extract_td_elements(page, xpath, day)
            # # Click on the date picker input to open the dropdown for group by
            # await page.click("#s2id_detailedstatisticssearch-groupby > a")

            # # Wait for the dropdown to be visible
            # await page.wait_for_selector("#select2-result-label-21")

            # # Select the appropriate group by option
            # await page.click("#select2-result-label-21")

            # Let the script wait to ensure all actions are complete before closing
            await asyncio.sleep(5)

# Call the main function to run the code
asyncio.run(main())
