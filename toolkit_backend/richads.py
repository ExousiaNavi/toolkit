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

async def main():
    
        
    async with async_playwright() as p:
        while True:
            keywords="richadspkr"
            email="abiralmilan1014@gmail.com"
            password="B@j!qwe@4444"
            link="https://my.richads.com/login"
            creative_id=['3268137', '3352123']
    
            browser = await p.chromium.launch(headless=False)
            page = await browser.new_page()
            await page.goto(link)
            
            try:
                # await solve_recaptcha(page)
                if not await fill_login_form(page, email, password):
                    return {"status": 400, "text": "Failed to fill the login form."}
                
                if not await submit_form(page):
                    return {"status": 400, "text": "Failed to submit the login form."}
                
                
                    
                # await solve_recaptcha(page)
                # await asyncio.sleep(5)
                
                break  # Exit the loop if successful
            
            except Exception as e:
                print(f"[ERROR] An unexpected error occurred: {e}")
            finally:
                await browser.close()

        if not await report(page):
            return {"status": 400, "text": "Failed to click report button."}
                
        if not await set_yesterdays_date(page, creative_id):
            return {"status": 400, "text": "Failed to click report button."}
        await asyncio.sleep(5)
        
async def get_yesterday_date():
    yesterday = datetime.now() - timedelta(days=1)
    return yesterday.year, yesterday.month, yesterday.day

# Wait for the page to load
async def wait_for_navigation(page):
    try:
        await page.wait_for_load_state('networkidle')
        return True
    except PlaywrightTimeoutError:
        logging.error("Navigation wait timeout.")
        return False
    
# Fill the login form
async def fill_login_form(page, email, password):
    try:
        await page.fill('input[name="email"]', email)
        await page.fill('input[name="password"]', password)
        return True
    except Exception as e:
        logging.error(f"Error filling login form: {e}")
        return False
                  
# Submit the form
async def submit_form(page):
    try:
        loginbtn = 'body > app-root > app-main-layout > app-unauthorized-layout > div > div > app-login > app-unauthorized-card > div > div.button-container > button'
        await page.click(loginbtn)
        await wait_for_navigation(page)
        
        is_error_visible = await page.is_visible('div.error.ng-star-inserted')
        if is_error_visible:
            print("Error message is visible.")
            await solve_recaptcha(page)
            if not await submit_form(page):
                return {"status": 400, "text": "Failed to submit the login form."}
        else:
            print("Error message is not visible.")
            return False
        return True
    except PlaywrightTimeoutError:
        logging.error("Timeout during form submission.")
        return False

#click the navigation
async def report(page):
    try:
        reports = '//*[@id="navleftrowinitial"]/div[4]/a'
         # try using JavaScript to click the button
        try:
            await page.eval_on_selector(f'xpath={reports}', "element => element.click()")
            logging.info("report button clicked using JavaScript.")
        except PlaywrightTimeoutError:
            logging.warning("Failed to click the report button using JavaScript.")
            # Wait for the network to be idle after clicking
                
        await page.wait_for_load_state('networkidle')
        await asyncio.sleep(2)
        return True
    except PlaywrightTimeoutError:
        logging.error("Navigation failed")
        return False


#input yesterday
async def set_yesterdays_date(page, creativeId):
    try:
        # 
        
       # Calculate yesterday's date
        yesterday = datetime.now() - timedelta(1)
        # Format it as YYYY-MM-DD
        formatted_date = yesterday.strftime("%Y-%m-%d")
        # Create the date range string
        date_range = f"{formatted_date} - {formatted_date}"

        # Select the input field by ID and fill it with the date range
        await page.fill('#filter_date', date_range)
        await asyncio.sleep(2)
        yesterday_btn_option = '/html/body/div[14]/div[1]/ul/li[2]'
        # Attempt to click the campaign using JavaScript
        await page.eval_on_selector(f'xpath={yesterday_btn_option}', "element => element.click()")
        logging.info("yesterday_btn_option clicked using JavaScript.")
        
        
        key = '/html/body/div[11]/div[2]/div[4]/span/span[1]/span'
        campaign_id = '//*[@id="select2-filter_campaigns-results"]/li'
        input = '/html/body/div[11]/div[2]/div[4]/span/span[1]/span/ul/li/input'
        try:
            # Locate the input field inside the select2 dropdown
            await page.fill('.select2-search__field', creativeId[0])

            # Press Enter to select the highlighted option
            await page.press('.select2-search__field', 'Enter')

            logging.info(f"Keyword '{creativeId[0]}' inserted and selected successfully.")
        
        except PlaywrightTimeoutError:
            # Log a warning if the campaign click fails due to a timeout
            logging.warning("Failed to click the campaign using JavaScript.")
                
        
        return True
    except PlaywrightTimeoutError:
        logging.error("input yesterday wait timeout.")
        return False
    

async def solve_recaptcha(page):
    # Wait for the reCAPTCHA checkbox to be available and click it
    if await page.frame_locator('iframe').first.locator('div.recaptcha-checkbox-border').is_visible():
        await page.frame_locator('iframe').first.locator('div.recaptcha-checkbox-border').click()
        if await check_dos_captcha(page):
            raise Exception("Detected 'Try again later' message.")
        await solve_audio_challenge(page)
    else:
        print("ReCAPTCHA checkbox not found.")

async def check_dos_captcha(page):
    try:
        print("[INFO] Checking for the 'Try again later' message...")

        # Wait for iframe to load and become visible
        await page.wait_for_selector('iframe[title="recaptcha challenge expires in two minutes"]', timeout=2000)
        iframe = page.frame_locator('iframe[title="recaptcha challenge expires in two minutes"]')

        # Introducing a short delay to let the iframe content stabilize
        await asyncio.sleep(1)

        # Check for the 'Try again later' message within the iframe
        try_again_message_locator = iframe.locator('body > div > div > div:nth-child(1) > div.rc-doscaptcha-body > div > a')

        # Check if the 'Try again later' message is visible
        is_visible = await try_again_message_locator.is_visible(timeout=2000)

        if is_visible:
            text_content = await try_again_message_locator.inner_text()
            print(f"[INFO] 'Try again later' message detected: {text_content}")
            return True  # 'Try again later' message found
        else:
            print("[INFO] 'Try again later' message not visible.")
            return False  # 'Try again later' message not found

    except Exception as e:
        print(f"[ERROR] An error occurred while checking 'Try again later' message: {e}")
        # For debugging purposes, print the entire iframe text content if an error occurs
        try:
            all_text = await iframe.locator('body').inner_text()
            print(f"[DEBUG] Full text in iframe: {all_text}")
        except Exception as inner_e:
            print(f"[ERROR] Failed to retrieve iframe text content: {inner_e}")
        return True  # Assume 'Try again later' to prevent an infinite loop


async def solve_audio_challenge(page):
    audio_frame = page.frame_locator('iframe[title="recaptcha challenge expires in two minutes"]')
    audio_button = audio_frame.locator('button#recaptcha-audio-button')
    if await audio_button.is_visible():
        await audio_button.click()
        print("[INFO] Audio button clicked. Waiting for the audio source...")
        try:
            # Wait for the audio source to become visible with a timeout of 2 seconds
            await asyncio.wait_for(
                audio_frame.locator('#rc-audio > div.rc-audiochallenge-tdownload > a').wait_for(state='visible'),
                timeout=3
            )
            print("[INFO] Audio source is visible.")

            audio_source_locator = audio_frame.locator('#rc-audio > div.rc-audiochallenge-tdownload > a')
            await audio_source_locator.wait_for(state='visible')
            src = await audio_source_locator.get_attribute('href')
            print(f"[INFO] Audio src: {src}")
            download_audio(src, path_to_mp3)
            key = play_and_recognize_audio(path_to_mp3)
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

        except asyncio.TimeoutError:
            # If the locator is not visible within 2 seconds, handle accordingly
             raise Exception("Audio button not showing.")
            # print("not showing")  # You can add additional logic here if needed
        

def download_audio(url, file_path):
    """Downloads audio from the provided URL."""
    try:
        urllib.request.urlretrieve(url, file_path)
        print(f"[INFO] Audio downloaded successfully as '{file_path}'")
    except Exception as e:
        print(f"[ERROR] Failed to download audio: {e}")

def play_and_recognize_audio(ptmp3):
    """Plays the audio and attempts to recognize the CAPTCHA key."""
    try:
        sound = pydub.AudioSegment.from_mp3(ptmp3)
        sound.export(path_to_wav, format="wav")
        sample_audio = sr.AudioFile(path_to_wav)
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
        return key
    except sr.UnknownValueError:
        print("[ERROR] Google Speech Recognition could not understand the audio")
        return False
    except sr.RequestError as e:
        print(f"[ERROR] Could not request results from Google Speech Recognition service; {e}")
        return False

if __name__ == "__main__":
    asyncio.run(main())
