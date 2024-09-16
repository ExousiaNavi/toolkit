
import asyncio
import os
import urllib.request
import sys
import pydub
from pathlib import Path
import logging
import json
from datetime import datetime, timedelta
import speech_recognition as sr
from pydub.playback import play
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError

class ProfellerAdsAutomation:
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
                        browser = await p.chromium.launch(
                            headless=False,
                            args=[
                                "--disable-blink-features=AutomationControlled",  # Disables detection of automation tools
                                "--disable-infobars"  # Disables "Chrome is being controlled by automated software" message
                            ]
                            )
                        context = await browser.new_context(storage_state=state,
                                                            user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                                                            viewport={"width": 1280, "height": 720},   # Set the same viewport size as your regular browser
                                                            locale="en-US",                            # Set locale to match your regular browsing locale
                                                            timezone_id="America/New_York" 
                                                            )
                        page = await context.new_page()
                        await page.goto(self.dashboard)
                        logging.info("Loaded existing session.")
                    else:
                        browser = await p.chromium.launch(
                            headless=False,
                            args=[
                                "--disable-blink-features=AutomationControlled",  # Disables detection of automation tools
                                "--disable-infobars"  # Disables "Chrome is being controlled by automated software" message
                            ]
                            )
                        context = await browser.new_context(
                            user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
                            viewport={"width": 1280, "height": 720},   # Set the same viewport size as your regular browser
                            locale="en-US",                            # Set locale to match your regular browsing locale
                            timezone_id="America/New_York" 
                        )
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
            
                    await page.wait_for_load_state('load')
                
                    profeller = await self.scrapping(page)
                    
                    await page.wait_for_load_state('load')
                    await asyncio.sleep(5)

                    # state = await context.storage_state()
                    # Path(self.session_file_path).write_text(json.dumps(state))
                    # logging.info("Session saved.")
                    
                    logging.info(f"Collected: {profeller}")
                    return profeller
                    break  # Exit the loop if successful

                except Exception as e:
                    print(f"[ERROR] An unexpected error occurred: {e}")
                finally:
                    await context.close()
                    await browser.close()

        
    async def fill_login_form(self, page):
        try:
            await page.get_by_label("Email").click()
            await page.get_by_label("Email").fill(self.email)
            await page.get_by_label("Password").click()
            await page.get_by_label("Password").fill(self.password)
            # await page.get_by_text("Sign inor").click()
            
            return True
        except Exception as e:
            logging.error(f"Error filling login form: {e}")
            return False

    async def submit_form(self, page):
        try:
            await page.get_by_role("button", name="Log in").click()
            await asyncio.sleep(60)
            await page.get_by_role("button", name="Confirm").click()
            print('completed')         
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
        

    async def scrapping(self, page):
        print('Start the get request to fetch data...')
        
        request_url = "https://partners.propellerads.com/api/client/campaigns/?dateFrom=2024-09-12&dateTill=2024-09-12&orderBy=id&orderDest=desc&search=&page=1&perPage=100&tz=-0500&isArchived=0"
        
        # Execute the request inside the browser using JavaScript
        response = await page.evaluate(f"""
            fetch('{request_url}', {{
                method: 'GET',
                headers: {{
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }}
            }}).then(response => response.json())
        """)
        
        # Process the response
        if response:
            logging.info(f"Fetched data: {response}")
            return response
        else:
            logging.error("Failed to fetch data from the request.")
            return None


   
# Example usage in another file
if __name__ == "__main__":
    automation = ProfellerAdsAutomation(
        keywords="cthkpropadpop",
        email="babiebaraimagar@gmail.com",
        password="B@j!qwe@6666",
        link="https://partners.propellerads.com/#/auth",
        dashboard="https://partners.propellerads.com/#/statistics",
        platform="daoad",
        creative_id=['286293', '286733', '284858', '285126', '287030', '288497', '315278']
    )
    asyncio.run(automation.run())

