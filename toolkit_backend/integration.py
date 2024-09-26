import asyncio
import os
import urllib.request
import sys
import logging
import pydub
import aiohttp
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

class RichadsAutomation:
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
                
                    state = await context.storage_state()
                    Path(self.session_file_path).write_text(json.dumps(state))
                    logging.info("Session saved.")
                    
                    rch = await self.scrapping(page)
                    
                    await page.wait_for_load_state('load')
                    await asyncio.sleep(5)

                    
                    
                    logging.info(f"Collected: {rch}")
                    return rch
                    break  # Exit the loop if successful

                except Exception as e:
                    print(f"[ERROR] An unexpected error occurred: {e}")
                finally:
                    await context.close()
                    await browser.close()

        
    async def fill_login_form(self, page):
        try:
            await page.fill('input[name="email"]', self.email)
            await page.fill('input[name="password"]', self.password)
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        try:
            await page.get_by_role("button", name="Log In").click()
            await asyncio.sleep(2)
            # await page.frame_locator("iframe[name=\"carrot-notification-frame\"]").locator("#notification-close").click()
            # await asyncio.sleep(2)
            # Scroll down by 1000 pixels
            await page.mouse.wheel(0, 1000)

            await asyncio.sleep(5)
            # is_error_visible = await page.is_visible('div.error.ng-star-inserted')
            
            await self.solve_recaptcha(page)
            await page.get_by_role("button", name="Log In").click()
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
                await page.get_by_role("link", name="Reporting").click()
                await asyncio.sleep(2)
                await page.get_by_role("link", name="Switch to Legacy version").click()
                logging.info("Report button clicked using JavaScript.")
                return True
        except PlaywrightTimeoutError:
                logging.warning("Failed to click the report button using JavaScript.")
                return False
        

    # Example function to extract bearer token or specific cookies
    def get_bearer_token_from_cookies(self,page,session_state):
        origins = session_state.get('origins', [])
    
        # Iterate over the origins to find the relevant token
        for origin in origins:
            if origin.get('origin') == "https://my.richads.com":  # Replace with the correct origin
                local_storage = origin.get('localStorage', [])
                for item in local_storage:
                    if item['name'].lower() == 'token':  # Look for the token key
                        return item['value']  # Return the token value
        return None

    async def send_request(self, page, cid, dateT, raw_cookies, bearer_token):
       
        try:
            logging.info(f"Sending POST request for cid: {cid}...")

            
            cookies = {cookie['name']: cookie['value'] for cookie in raw_cookies}
            
           

            logging.info(f"bearer_token: {bearer_token}")
            async with aiohttp.ClientSession(cookies=cookies) as session:
                url = 'https://api.adx1.com/module/Cpmplatform/reportDruid/SimpleReport/'
                payload = {
                    'from': self.targetdate,
                    'to': self.targetdate,
                    'page': 1,
                    'period': 'yesterday',
                    'page_size': 1000,
                    'segment': 'day',
                    'segment_column': '',  # Assuming you need to define this field
                    'search_columns[0]': 'accepted_clicks',
                    'search_columns[1]': 'conversion_rate',
                    'search_columns[2]': 'conversions',
                    'search_columns[3]': 'media_cost',
                    'search_columns[4]': 'impressions',
                    'search_columns[5]': 'bids',
                    'search_columns[6]': 'ctr',
                    'search_columns[7]': 'roi',
                    'search_columns[8]': 'conversions_sum',
                    'search_columns[9]': 'conversions1',
                    'search_columns[10]': 'engaged_clicks',
                    'search_columns[11]': 'engaged_clicks_rate_100',
                    'report_by': 'advertiser',
                    'create_csv': 0,
                    'filter[creative_id][id][0]': cid,
                    'sort_property': 'timestamp',
                    'sort_direction': 'DESC'
                }

                headers = {
                    'Content-Type': 'application/x-www-form-urlencoded',  # Adjust based on what API expects
                }
                if bearer_token:
                    headers['Authorization'] = f'Bearer {bearer_token}' 
        
                async with session.post(url, headers=headers, data=payload) as response:
                    logging.info(f"Received response with status: {response.status}")
                    # Ensure 'data' is only initialized inside the correct scope
                    data = None
                    formattedData = None
                    if response.status == 200:
                        try:
                            data = await response.text()
                            parsed_data = json.loads(data)
                            logging.info(f"Data: {parsed_data}")
                        except json.JSONDecodeError as json_err:
                            logging.error(f"Failed to parse JSON for cid: {cid}, error: {json_err}")
                            return  # Exit the function if JSON parsing fails

                        # if 'data' in parsed_data and isinstance(parsed_data['data'], list):
                        #     for campaign in parsed_data['data']:
                        #         creative_id = cid
                        #         impressions = campaign.get('impressions', '0')
                        #         clicks = campaign.get('clicks', '0')
                        #         spending = campaign.get('cost', '0.00')
                        #         logging.info(f"Campaign ID: {creative_id}, Impressions: {impressions}, Clicks: {clicks}, Spending: {spending}")
                                
                        #         formattedData = {
                        #                 'creative_id': creative_id,
                        #                 'Impressions': impressions,
                        #                 'Clicks': clicks,
                        #                 'Spending': spending,
                        #             }
                            
                        #     return formattedData
                        # else:
                        #     logging.error(f"'data' field is missing or not in the expected format for cid: {cid}")
                    else:
                        logging.error(f"Request failed with status {response.status} for cid: {cid}")
                        return  # Exit the function if the request fails
        except Exception as e:
            logging.error(f"An error occurred for cid: {cid}: {str(e)}")
       
       
    async def scrapping(self, page):
        
        try:
            os.makedirs(self.session_dir, exist_ok=True)
            session_state = json.loads(Path(self.session_file_path).read_text())
            raw_cookies = session_state.get('cookies', [])
             # Extract Bearer Token (if necessary)
            bearer_token = self.get_bearer_token_from_cookies(page,session_state)
            results = []
            for cid in self.creative_id:
                try:
                    responseData = await self.send_request(page, cid, self.targetdate, raw_cookies, bearer_token)
                    results.append(responseData)
                except Exception as e:
                    # If any error occurs (including Locator.click timeout), assign default values
                    logging.error(f"An error occurred with creative_id '{cid}': {str(e)}. Defaulting values to 0.")
            logging.info(f"Response: {results}")
            # return results

        except Exception as e:
            logging.error(f"An error occurred while scraping data: {str(e)}")
            return None
        except PlaywrightTimeoutError:
            logging.error("Input yesterday wait timeout.")
            return False

    async def solve_recaptcha(self, page):
        # # Wait for the iframe to appear on the page
        iframe_element = await page.wait_for_selector('iframe[title="reCAPTCHA"]')
        
        if await page.wait_for_selector('iframe[title="reCAPTCHA"]'):
            # Get the iframe's content frame
            iframe = await iframe_element.content_frame()
            # Now you can interact with elements inside the iframe
            recaptcha_checkbox = await iframe.wait_for_selector('#recaptcha-anchor')
            await recaptcha_checkbox.click()
            if await self.check_dos_captcha(page):
                raise Exception("Detected 'Try again later' message.")
            await self.solve_audio_challenge(page)
        else:
            print("ReCAPTCHA checkbox not found.")

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
# Example usage in another file
if __name__ == "__main__":
    automation = RichadsAutomation(
        keywords="riadldbjbd",
        email="bo.cc@chengyi-1.com",
        password="B@j!09876**1",
        link="https://my.richads.com/login",
        creative_id=['3318238'],
        dashboard="https://my.richads.com/campaigns",
        platform="richads",
        targetdate = '2024-09-23'
    )
    asyncio.run(automation.run())
