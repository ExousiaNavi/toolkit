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

class DaoadAutomation:
    def __init__(self, keywords, email, password, link, creative_id, dashboard, platform):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
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
                    
                    
                    state = await context.storage_state()
                    Path(self.session_file_path).write_text(json.dumps(state))
                    logging.info("Session saved.")
            
                    if not await self.report(page):
                        return {"status": 400, "text": "Failed to click report button."}
                    await page.wait_for_load_state('load')
                
                    daoad = await self.scrapping(page)
                    
                    await page.wait_for_load_state('load')
                    await asyncio.sleep(5)

                    # state = await context.storage_state()
                    # Path(self.session_file_path).write_text(json.dumps(state))
                    # logging.info("Session saved.")
                    
                    logging.info(f"Collected: {daoad}")
                    return daoad
                    break  # Exit the loop if successful

                except Exception as e:
                    print(f"[ERROR] An unexpected error occurred: {e}")
                finally:
                    await context.close()
                    await browser.close()

        
    async def fill_login_form(self, page):
        try:
            await page.get_by_placeholder("Email").fill(self.email)
            await asyncio.sleep(2)
            await page.get_by_placeholder("Password").fill(self.password)
            await asyncio.sleep(2)
            await page.get_by_role("button", name="OK").click()
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        try:
            await page.get_by_role("button", name="Log In").click()
            await asyncio.sleep(5)
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
            await page.get_by_role("button", name="Close").click()
            await asyncio.sleep(2)
            await page.get_by_role("link", name=" Statistics").click()
            await asyncio.sleep(2)
            await page.get_by_role("link", name="Popunder").click()
            logging.info("Report button clicked using JavaScript.")
            return True
        except PlaywrightTimeoutError:
            logging.warning("Failed to click the report button using JavaScript.")
            return False
                
        

    async def scrapping(self, page):
        n = True
        try:
            await page.wait_for_load_state('networkidle')
            await asyncio.sleep(5)
            
            await page.get_by_placeholder("Date range").click()
            await page.get_by_text("Yesterday").click()
           
            
            results = []
            for cid in self.creative_id:
                
                try:
                    # Wait for the input field to be available and fill it with the value
                    await asyncio.sleep(2)
                    # Click the button using a CSS selector
                    await page.locator('button.btn.dropdown-toggle[data-id="campaignId"]').click()
                    await page.get_by_role("button", name="Deselect All").click()
            
                    # Wait for the search textbox to be visible
                    await page.wait_for_selector('role=textbox[name="Search"]', timeout=5000)
                    
                    # Force focus on the textbox before interacting
                    search_box = page.get_by_role("textbox", name="Search")
                    await search_box.click()
                    await search_box.fill("")  # Clear the search field
                    await search_box.fill(cid)  # Fill the field with the creative ID
                    
                    await asyncio.sleep(2)
                    
                    await page.get_by_role("button", name="Deselect All").click()
                    
                    await page.get_by_role("button", name="Select All", exact=True).click()
                    await page.get_by_role("button", name="Show").click()
                    
                    await asyncio.sleep(5)
                    await page.mouse.wheel(0, 1000)
                    print("Successfully filled the Creative ID field.")
                    n = False
                except PlaywrightTimeoutError:
                    print("Filling the Creative ID field timed out.")
                    n = True
                
                if n:
                   data = {
                        'creative_id': cid,
                        'Impressions': 0,
                        'Clicks': 0,
                        'Spending': 0,
                        # 'Profit': profit.strip()
                    } 
                else:
                    # Retrieve the values of the cells using the provided XPath and strip whitespace
                    visible_impressions = await page.locator('tfoot tr.text-bold.text-grey-600 td:nth-child(3) span').inner_text()
                    
                    # Optionally, handle formatting, e.g., converting "39.27 K" to "39270"
                    if 'K' in visible_impressions:
                        visible_impressions = float(visible_impressions.replace('K', '').strip()) * 1000
                        impressions = str(int(visible_impressions))  # Convert it back to string
                    else:
                        impressions = visible_impressions.strip()  # No formatting needed
                    
                    clicks = 0
                    spending = (await page.locator('tfoot tr.text-bold.text-grey-600 td:nth-child(5)').get_attribute('data-value')).strip()

                    await page.screenshot(path=f"daoad_{cid}.png", full_page=True)

                    data = {
                        'creative_id': cid,
                        'Impressions': impressions,
                        'Clicks': clicks,
                        'Spending': spending,
                        # 'Profit': profit.strip()
                    }
                   
                results.append(data)
                logging.info(f"Data for creative_id '{cid}' collected: {data}")
                await asyncio.sleep(5)    
                #scroll up
                await page.mouse.wheel(0, -1000)
                # await page.get_by_text("×").click()
                # logging.info(f"Collected: {results}")
            return results

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
# if __name__ == "__main__":
#     automation = DaoadAutomation(
#         keywords="daoadpkr",
#         email="abiralmilan1014@gmail.com",
#         password="B@j!qwe@4444",
#         link="https://dao.ad/login",
#         dashboard="https://dao.ad/manage/dashboard",
#         platform="daoad",
#         creative_id=['315278']
#     )
#     asyncio.run(automation.run())

