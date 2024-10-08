import asyncio
import os
import urllib.request
import sys
import logging
import pydub
from datetime import datetime, timedelta
import speech_recognition as sr
from pydub.playback import play

from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError

# Set the path to your local FFmpeg and FFprobe
ffmpeg_path = os.path.normpath(os.path.join(os.getcwd(), 'ffmpeg.exe'))
ffprobe_path = os.path.normpath(os.path.join(os.getcwd(), 'ffprobe.exe'))

# Set environment variables
os.environ["PATH"] += os.pathsep + ffmpeg_path
os.environ["PATH"] += os.pathsep + ffprobe_path

path_to_mp3 = os.path.normpath(os.path.join(os.getcwd(), "sample.mp3"))
path_to_wav = os.path.normpath(os.path.join(os.getcwd(), "sample.wav"))
    
import asyncio
import os
import urllib.request
import sys
from pathlib import Path
import logging
import pydub
import json
from datetime import datetime, timedelta
import speech_recognition as sr
from pydub.playback import play
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError

class ClickAduAutomation:
    def __init__(self, keywords, email, password, link, creative_id, dashboard, platform, targetdate):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
        self.targetdate = targetdate
        self.ffmpeg_path = os.path.normpath(os.path.join(os.getcwd(), 'ffmpeg.exe'))
        self.ffprobe_path = os.path.normpath(os.path.join(os.getcwd(), 'ffprobe.exe'))
        self.path_to_mp3 = os.path.normpath(os.path.join(os.getcwd(), "sample.mp3"))
        self.path_to_wav = os.path.normpath(os.path.join(os.getcwd(), "sample.wav"))
        self.session_dir = "sessions"
        self.session_file_name = f"{keywords.replace(' ', '_')}_{datetime.now().strftime('%Y-%m-%d')}.json"
        self.session_file_path = os.path.join(self.session_dir, self.session_file_name)
        
        os.environ["PATH"] += os.pathsep + self.ffmpeg_path
        os.environ["PATH"] += os.pathsep + self.ffprobe_path

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
                        
                        # await self.solve_recaptcha(page)
                    
                        if not await self.submit_form(page):
                            return {"status": 400, "text": "Failed to submit the login form."}
                    
                    await page.wait_for_load_state('load')
                    
                    if not await self.report(page):
                        return {"status": 400, "text": "Failed to click report button."}
                    await page.wait_for_load_state('load')
                
                    rch = await self.scrapping(page)
                    
                    await page.wait_for_load_state('load')
                    await asyncio.sleep(5)

                    state = await context.storage_state()
                    Path(self.session_file_path).write_text(json.dumps(state))
                    logging.info("Session saved.")
                    
                    # logging.info(f"Collected: {rch}")
                    return rch
                    break  # Exit the loop if successful

                except Exception as e:
                    print(f"[ERROR] An unexpected error occurred: {e}")
                finally:
                    await context.close()
                    await browser.close()

        
    async def fill_login_form(self, page):
        try:
            # await page.fill('input[name="email"]', self.email)
            # await page.fill('input[name="password"]', self.password)
            await page.get_by_role("button", name="Sign in").click()
            await page.get_by_role("link", name="Advertiser").nth(1).click()
            await page.get_by_label("Email").click()
            await page.get_by_label("Email").fill(self.email)
            await page.locator("#oldPasswordPassword").click()
            await page.locator("#oldPasswordPassword").fill(self.password)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        try:
            await page.get_by_role("button", name="Log in").click()
            await asyncio.sleep(2)
            # await page.frame_locator("iframe[name=\"carrot-notification-frame\"]").locator("#notification-close").click()
            # await asyncio.sleep(2)
            # Scroll down by 1000 pixels
            await page.mouse.wheel(0, 1000)

            await asyncio.sleep(5)
            # is_error_visible = await page.is_visible('div.error.ng-star-inserted')
            
            status = await self.solve_recaptcha(page)
            print(status)
            if status != 'error': # means recaptcha is true and solve
                print('resubmit the form..')
                await page.get_by_role("button", name="Log in").click()
            
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
        await asyncio.sleep(5)
        try:
                await page.get_by_role("button", name="Campaign", exact=True).click()
                await page.get_by_role("link", name="Campaigns").click()
                await asyncio.sleep(2)
                logging.info("Campaigns button clicked using JavaScript.")
                return True
        except PlaywrightTimeoutError:
                logging.warning("Failed to click the Campaigns button using JavaScript.")
                return False
        

    # Function to locate, print, and click the matching `td` elements under the calendar
    async def extract_td_elements(self, page, header_tbody_xpath, previous_days):
        
        print(f"Target dates to click: {previous_days}")
        await asyncio.sleep(5)
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
                if td_text == str(previous_days):
                    print(f"Clicking on day {td_text}")
                    await td.dblclick()
                    await asyncio.sleep(3)
                    
                    # #click the button apply
                    # await page.click("//div[@class='drp-buttons']//button[contains(@class, 'applyBtn')]")
                    await asyncio.sleep(3)

    # Function to locate and check the month headers in both calendars
    async def dateHeader(self, page):
        # Define XPaths for selecting the left and right date headers
        calendar_paths = {
            # "left": "//div[@class='drp-calendar left']//th[@class='month']",
            "right": "//div[@class='date_range__calendar-col date_range__calendar--hide']//table[contains(@class, 'date_range__calendar-table ng-star-inserted')]//th[contains(@class, 'month')]"
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
                        # header_tbody_xpath = f"//div[@class='drp-calendar {position}']//td"
                        header_tbody_xpath = f"//div[@class='date_range__calendar-col date_range__calendar--hide']//table[contains(@class, 'date_range__calendar-table ng-star-inserted')]//td[contains(@class, 'available')]"
                        # await extract_td_elements(page, header_tbody_xpath, tday)
                        return header_tbody_xpath



    async def scrapping(self, page):
        try:  
            await page.wait_for_load_state('networkidle')
            await asyncio.sleep(5)
            
            await page.get_by_role("button", name="Today").click()
            # await page.get_by_role("button", name="Yesterday").click()
            # XPath for the input field (assuming it's the first input in the range)
            # Clear the input field explicitly (Playwright's fill() usually does this automatically)
            # Use the XPath expression in a Playwright locator
            await page.locator("//html/body/cl-root/div/cl-layout-authenticated/div/div/cl-layout-body/cl-page-content/div/cl-campaigns-list/div/div[2]/cl-campaigns-list-actions/div[2]/cl-date-range/div/button").click()

            await asyncio.sleep(3)
            xpath = await self.dateHeader(page)
            await self.extract_td_elements(page, xpath, self.targetdate)
            
            # await page.locator("cl-date-range").get_by_role("button", name="Set").click()
            await page.locator("//div[@class='date_range__calendar-footer']//button[2]").click()
        except PlaywrightTimeoutError:
            logging.error("Setting of Yesterday failed")
        
        try:
            results = []
            for cid in self.creative_id:
                cid_found = False  # Flag to check if cid is found in any row
                
                # Wait for the table to appear
                await page.wait_for_selector('table.table__content')
                # Select all rows in the tbody
                rows = await page.query_selector_all('table.table__content tbody tr')
                
                for row in rows:
                    # Extract the td elements for columns 3, 5, 6, and 11
                    creative_id = await row.query_selector('td:nth-child(3)')
                    impression = await row.query_selector('td:nth-child(5)')
                    clicks = await row.query_selector('td:nth-child(6)')
                    spending = await row.query_selector('td:nth-child(11)')

                    # Extract text content from the selected td elements
                    ccid = await creative_id.inner_text() if creative_id else '0'
                    impressions = await impression.inner_text() if impression else '0'
                    clicks_value = await clicks.inner_text() if clicks else '0'
                    spending_value = await spending.inner_text() if spending else '0'

                    # If the current row's creative ID matches the desired cid
                    if cid == ccid:
                        cid_found = True
                        data = {
                            'creative_id': cid,
                            'Impressions': impressions,
                            'Clicks': clicks_value,
                            'Spending': spending_value,
                        }
                        results.append(data)
                        break  # Continue to the next cid after processing the row

                # If cid was not found, append default 0 values
                if not cid_found:
                    data = {
                        'creative_id': cid,
                        'Impressions': '0',
                        'Clicks': '0',
                        'Spending': '0',
                    }
                    results.append(data)
                
                # logging.info(f"Data for creative_id '{cid}' collected: {data}")

            logging.info(f"Collected: {results}")
            return results

        except Exception as e:
            logging.error(f"An error occurred while scraping data: {str(e)}")
            return False


    async def solve_recaptcha(self, page):
        try:
            # # Wait for the iframe to appear on the page
            iframe_element = await page.wait_for_selector('iframe[title="reCAPTCHA"]', timeout=3000)
            
            if await page.wait_for_selector('iframe[title="reCAPTCHA"]'):
                # Get the iframe's content frame
                iframe = await iframe_element.content_frame()
                # Now you can interact with elements inside the iframe
                recaptcha_checkbox = await iframe.wait_for_selector('#recaptcha-anchor')
                await recaptcha_checkbox.click()
                if await self.check_dos_captcha(page):
                    raise Exception("Detected 'Try again later' message.")
                await self.solve_audio_challenge(page)
                
                return 'success'
            else:
                print("ReCAPTCHA checkbox not found.")
                
                return 'error'
        except Exception as e:
            print(f"[ERROR] reCAPTCHA is not visible: {e}")
            return 'error'
    async def check_dos_captcha(self, page):
        try:
            print("[INFO] Checking for the 'Try again later' message...")
            await page.wait_for_selector('iframe[title="recaptcha challenge expires in two minutes"]', timeout=2000)
            iframe = page.frame_locator('iframe[title="recaptcha challenge expires in two minutes"]')
            await asyncio.sleep(1)
            try_again_message_locator = iframe.locator('body > div > div > div:nth-child(1) > div.rc-doscaptcha-body > div > a')
            is_visible = await try_again_message_locator.is_visible(timeout=2000)
            if is_visible:
                text_content = await try_again_message_locator.inner_text()
                print(f"[INFO] 'Try again later' message detected: {text_content}")
                return True
            else:
                print("[INFO] 'Try again later' message not visible.")
                return False
        except Exception as e:
            print(f"[ERROR] An error occurred while checking 'Try again later' message: {e}")
            try:
                all_text = await iframe.locator('body').inner_text()
                print(f"[DEBUG] Full text in iframe: {all_text}")
            except Exception as inner_e:
                print(f"[ERROR] Failed to retrieve iframe text content: {inner_e}")
            return True
        
    async def solve_audio_challenge(self, page):
        audio_frame = page.frame_locator('iframe[title="recaptcha challenge expires in two minutes"]')
        audio_button = audio_frame.locator('button#recaptcha-audio-button')
        if await audio_button.is_visible():
            await audio_button.click()
            print("[INFO] Audio button clicked. Waiting for the audio source...")
            try:
                audio_source = await audio_frame.locator('audio').get_attribute('src')
                if not audio_source:
                    raise Exception("No audio source found.")
                print(f"[INFO] Audio source found: {audio_source}")

                urllib.request.urlretrieve(audio_source, self.path_to_mp3)
                print("[INFO] Audio CAPTCHA downloaded. Converting to WAV format...")
                # sound = pydub.AudioSegment.from_mp3(self.path_to_mp3)
                print(f"[INFO] Audio src: {audio_source}")
                
                # download_audio(audio_source, self.path_to_mp3)
                """Downloads audio from the provided URL."""
                try:
                    urllib.request.urlretrieve(audio_source, self.path_to_mp3)
                    print(f"[INFO] Audio downloaded successfully as '{self.path_to_mp3}'")
                except Exception as e:
                    print(f"[ERROR] Failed to download audio: {e}")
        
                """Plays the audio and attempts to recognize the CAPTCHA key."""
                try:
                    sound = pydub.AudioSegment.from_mp3(self.path_to_mp3)
                    sound.export(self.path_to_wav, format="wav")
                    sample_audio = sr.AudioFile(self.path_to_wav)
                except Exception as e:
                    print(f"[ERROR] Failed to convert MP3 to WAV: {e}")
                    sys.exit("[ERR] Please run the program as administrator or ensure that FFmpeg is correctly set up.")

                try:
                    play(sound)
                except Exception as e:
                    print(f"[ERROR] Failed to play audio: {e}")

                r = sr.Recognizer()
                with sample_audio as source:
                    audio = r.record(source)

                try:
                    key = r.recognize_google(audio)
                    print(f"[INFO] Recaptcha Passcode: {key}")
                except sr.UnknownValueError:
                    print("[ERROR] Google Speech Recognition could not understand the audio")
                    key = False
                except sr.RequestError as e:
                    print(f"[ERROR] Could not request results from Google Speech Recognition service; {e}")
                    key = False
                    
                if not key:
                    raise Exception("Failed to recognize the CAPTCHA passcode.")
                audio_input_locator = audio_frame.locator('#audio-response')
                if await audio_input_locator.is_visible():
                    await audio_input_locator.fill(key)
                    print(f"[INFO] Recaptcha Passcode entered: {key}")
                    verify_button_locator = audio_frame.locator('#recaptcha-verify-button')
                    await verify_button_locator.click()
                    print("[INFO] Recaptcha verify button clicked.")
                    await asyncio.sleep(2)

            except PlaywrightTimeoutError:
                logging.error("Audio challenge failed due to timeout.")
                raise Exception("Audio challenge failed due to timeout.")
        