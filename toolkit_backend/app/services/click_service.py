import sys
import os
import json
import logging
from datetime import datetime, timedelta
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeoutError
import asyncio

# Get the parent directory and add it to sys.path
# sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from app.platforms.adcash import AdcashScraper
from app.platforms.adxad import AdxadScraper
from app.platforms.trafficstars import TrafficStarsAutomation
from app.platforms.traffinomads import TrafficNomadsAutomation
from app.platforms.exoclick import ExoclickAutomation
from app.platforms.richads import RichadsAutomation
from app.platforms.daoad import DaoadAutomation

# Configure logging
logging.basicConfig(level=logging.INFO)

class ClickAutomation:
    #email='abiralmilan1014@gmail.com' password='B@j!qwe@4444' link='https://bajipartners.com/page/affiliate/login.jsp' creative_id=['20948', '20947', '22698']
    def __init__(self, keywords: str, email: str, password: str, link: str, creative_id: list[str], dashboard: str, platform: str, max_retries=3):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
        self.max_retries = max_retries
        self.session = None  # Session management for authenticated requests

     # get the date yesterday
    async def get_yesterday_date(self):
        yesterday = datetime.now() - timedelta(days=1)
        return yesterday.year, yesterday.month, yesterday.day
    
    async def fetch_info(self):
        data = {}
        scraper = None  # Initialize scraper to None
        
        try:
            logging.info(f"Platform: {self.platform}")
            if self.platform == 'adcash':
                scraper = AdcashScraper(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
            elif self.platform == 'adxad':
                scraper = AdxadScraper(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
            elif self.platform == 'trafficstars':
                scraper = TrafficStarsAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
            elif self.platform == 'trafficnomads':
                scraper = TrafficNomadsAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
            elif self.platform == 'exoclick':
                scraper = ExoclickAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
            elif self.platform == 'richads':
                scraper = RichadsAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
            elif self.platform == 'daoad':
                scraper = DaoadAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)

            # Ensure scraper was initialized before trying to use it
            if scraper:
                result = await scraper.run()
                data = {
                    "status": 200,
                    "text": "Data Fetched successfully",
                    "title": "Fetch Completed!",
                    "icon": "success",
                    "clicks_and_impr": result,
                }
            else:
                raise ValueError("No valid scraper was initialized.")
            
        except Exception as e:
            logging.error(f"An error occurred during the automation process: {e}")
            data = {
                "status": 500,
                "text": "An error occurred during fetching data",
                "title": "Fetch Failed",
                "icon": "error",
                "error": str(e)
            }
            
        finally:
            return data