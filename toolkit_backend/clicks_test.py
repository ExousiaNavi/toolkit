import os
import logging
import pydub
import urllib.request
import sys
import speech_recognition as sr
from pydub.playback import play
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError
import asyncio

# Configure logging
logging.basicConfig(level=logging.INFO)

# Set the path to your local FFmpeg and FFprobe
ffmpeg_path = os.path.normpath(os.path.join(os.getcwd(), 'ffmpeg.exe'))
ffprobe_path = os.path.normpath(os.path.join(os.getcwd(), 'ffprobe.exe'))

# Set environment variables
os.environ["PATH"] += os.pathsep + ffmpeg_path
os.environ["PATH"] += os.pathsep + ffprobe_path

path_to_mp3 = os.path.normpath(os.path.join(os.getcwd(), "sample.mp3"))
path_to_wav = os.path.normpath(os.path.join(os.getcwd(), "sample.wav"))

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

# Solve reCAPTCHA
async def solve_recaptcha(page):
    # Wait for the reCAPTCHA checkbox to be available and click it
    try:
        recaptcha_checkbox = page.frame_locator('iframe').first.locator('div.recaptcha-checkbox-border')
        if await recaptcha_checkbox.is_visible():
            await recaptcha_checkbox.click()
            if await check_dos_captcha(page):
                raise Exception("Detected 'Try again later' message.")
            await solve_audio_challenge(page)
        else:
            print("ReCAPTCHA checkbox not found.")
    except PlaywrightTimeoutError as e:
        logging.error(f"Timeout while waiting for reCAPTCHA: {e}")

async def check_dos_captcha(page):
    try:
        print("[INFO] Checking for the 'Try again later' message...")
        await page.wait_for_selector('iframe[title="recaptcha challenge expires in two minutes"]', timeout=5000)
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
        return True

async def solve_audio_challenge(page):
    audio_frame = page.frame_locator('iframe[title="recaptcha challenge expires in two minutes"]')
    audio_button = audio_frame.locator('button#recaptcha-audio-button')
    if await audio_button.is_visible():
        await audio_button.click()
        print("[INFO] Audio button clicked. Waiting for the audio source...")
        try:
            await audio_frame.locator('#rc-audio > div.rc-audiochallenge-tdownload > a').wait_for(state='visible', timeout=5000)
            audio_source_locator = audio_frame.locator('#rc-audio > div.rc-audiochallenge-tdownload > a')
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
        except PlaywrightTimeoutError:
            print("[ERROR] Audio source did not become visible in time.")

def download_audio(url, file_path):
    try:
        urllib.request.urlretrieve(url, file_path)
        print(f"[INFO] Audio downloaded successfully as '{file_path}'")
    except Exception as e:
        print(f"[ERROR] Failed to download audio: {e}")

def play_and_recognize_audio(ptmp3):
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

# Submit the form
async def submit_form(page):
    try:
        await page.click('#loginuser')
        await wait_for_navigation(page)
        return True
    except PlaywrightTimeoutError:
        logging.error("Timeout during form submission.")
        return False

# Function to fetch information from the webpage
async def fetch_info(keywords, email, password, link, creative_id):
    print(f"Creative IDs: {creative_id}")
    year, month, day = await get_yesterday_date()
    async with async_playwright() as p:
        while True:
            browser = await p.chromium.launch(headless=False)
            page = await browser.new_page()
            await page.goto(link)
            
            try:
                if not await fill_login_form(page, email, password):
                    return {"status": 400, "text": "Failed to fill the login form."}
                if not await submit_form(page):
                    return {"status": 400, "text": "Failed to submit the login form."}
                
                await solve_recaptcha(page)
                print("You can start clicking the submit button")
                break  # Exit the loop if successful
            except Exception as e:
                print(f"[ERROR] An unexpected error occurred: {e}")
            finally:
                await browser.close()

if __name__ == "__main__":
    asyncio.run(fetch_info(
        keywords="trafficnompkr",
        email="abiralmilan1014@gmail.com",
        password="B@j!qwe@4444",
        link="https://partners.trafficnomads.com/?login=adv",
        creative_id=['20948', '20947', '22698']
    ))
