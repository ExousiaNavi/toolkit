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

class TrafficNomadsAutomation:
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
                        
                        await self.solve_recaptcha(page)
                    
                        if not await self.submit_form(page):
                            return {"status": 400, "text": "Failed to submit the login form."}
                    
                    await page.wait_for_load_state('load')
                    
                    if not await self.report(page):
                        return {"status": 400, "text": "Failed to click report button."}
                    await page.wait_for_load_state('load')
                
                    nomads = await self.scrapping(page)
                    
                    await page.wait_for_load_state('load')
                    await asyncio.sleep(5)

                    state = await context.storage_state()
                    Path(self.session_file_path).write_text(json.dumps(state))
                    logging.info("Session saved.")
                    return nomads
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
            await page.click('#loginuser')
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
            reports = '//*[@id="navleftrowinitial"]/div[4]/a'
            try:
                await page.eval_on_selector(f'xpath={reports}', "element => element.click()")
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
            yesterday = datetime.now() - timedelta(1)
            formatted_date = yesterday.strftime("%Y-%m-%d")
            date_range = f"{formatted_date} - {formatted_date}"

            await page.fill('#filter_date', date_range)
            await asyncio.sleep(2)
            yesterday_btn_option = '/html/body/div[14]/div[1]/ul/li[2]'
            await page.eval_on_selector(f'xpath={yesterday_btn_option}', "element => element.click()")
            logging.info("Yesterday button option clicked using JavaScript.")

            results = []
            for cid in self.creative_id:
                await page.get_by_role("searchbox", name="All").nth(2).fill(cid)
                await page.locator('.select2-results__option.select2-results__option--highlighted').click()
                logging.info(f"Keyword '{cid}' inserted and selected successfully.")
                
                await page.get_by_role("button", name="Apply").click()
                await asyncio.sleep(10)
                await page.wait_for_selector("#DataTables_Table_0 tfoot tr", state="visible", timeout=60000)
                logging.info("Table footer loaded.")
                await page.mouse.wheel(0, 1000)
                tr_locator = page.locator("#DataTables_Table_0 tfoot tr")

                impressions = await tr_locator.locator("th").nth(1).text_content()
                clicks = await tr_locator.locator("th").nth(2).text_content()
                spending = await tr_locator.locator("th").nth(3).text_content()
                # profit = await tr_locator.locator("th").last.text_content()

                await page.screenshot(path=f"trafficnomads_{cid}.png", full_page=True)

                data = {
                    'creative_id': cid,
                    'Impressions': impressions.strip(),
                    'Clicks': clicks.strip(),
                    'Spending': spending.strip(),
                    # 'Profit': profit.strip()
                }
                results.append(data)
                logging.info(f"Data for creative_id '{cid}' collected: {data}")
                
                #scroll up
                await page.mouse.wheel(0, -1000)
                await page.get_by_text("×").click()
            
            logging.info(f"Collected: {results}")
            return results

        except Exception as e:
            logging.error(f"An error occurred while scraping data: {str(e)}")
            return None
        except PlaywrightTimeoutError:
            logging.error("Input yesterday wait timeout.")
            return False

    async def solve_recaptcha(self, page):
        if await page.frame_locator('iframe').first.locator('div.recaptcha-checkbox-border').is_visible():
            await page.frame_locator('iframe').first.locator('div.recaptcha-checkbox-border').click()
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
                

    # async def solve_audio_challenge(self, page):
    #     audio_frame = page.frame_locator('iframe[title="recaptcha challenge expires in two minutes"]')
    #     audio_button = audio_frame.locator('button#recaptcha-audio-button')
    #     if await audio_button.is_visible():
    #         await audio_button.click()
    #         print("[INFO] Audio button clicked. Waiting for the audio source...")
    #         try:
    #             audio_source = await audio_frame.locator('audio').get_attribute('src')
    #             if not audio_source:
    #                 raise Exception("No audio source found.")
    #             print(f"[INFO] Audio source found: {audio_source}")

    #             urllib.request.urlretrieve(audio_source, self.path_to_mp3)
    #             print("[INFO] Audio CAPTCHA downloaded. Converting to WAV format...")
    #             sound = pydub.AudioSegment.from_mp3(self.path_to_mp3)
    #             sound.export(self.path_to_wav, format="wav")
    #             audio = pydub.AudioSegment.from_wav(self.path_to_wav)
    #             play(audio)

    #             recognizer = sr.Recognizer()
    #             with sr.AudioFile(self.path_to_wav) as source:
    #                 audio = recognizer.record(source)
    #             print("[INFO] Transcribing audio...")
    #             transcription = recognizer.recognize_google(audio)
    #             print(f"[INFO] Transcription: {transcription}")

    #             transcription_input = audio_frame.locator('input[type="text"]')
    #             await transcription_input.fill(transcription)
    #             verify_button = audio_frame.locator('button#recaptcha-verify-button')
    #             await verify_button.click()

    #         except PlaywrightTimeoutError:
    #             logging.error("Audio challenge failed due to timeout.")

    # async def fetch_info(self):
    #     await self.main()

# Example usage in another file
# if __name__ == "__main__":
#     automation = TrafficNomadsAutomation(
#         keywords="trafficnompkr",
#         email="abiralmilan1014@gmail.com",
#         password="B@j!qwe@4444",
#         link="https://bajipartners.com/page/affiliate/login.jsp",
#         creative_id=["20948", "20947", "22698"]
#     )
#     asyncio.run(automation.fetch_info())
