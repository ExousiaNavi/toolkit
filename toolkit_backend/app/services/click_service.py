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
from app.platforms.clickadu import ClickAduAutomation

# Configure logging
logging.basicConfig(level=logging.INFO)

class ClickAutomation:
    #email='abiralmilan1014@gmail.com' password='B@j!qwe@4444' link='https://bajipartners.com/page/affiliate/login.jsp' creative_id=['20948', '20947', '22698']
    def __init__(self, keywords: str, email: str, password: str, link: str, creative_id: list[str], dashboard: str, platform: str, targetdate: str, max_retries=3):
        self.keywords = keywords
        self.email = email
        self.password = password
        self.link = link
        self.creative_id = creative_id
        self.dashboard = dashboard
        self.platform = platform
        self.targetdate = targetdate
        self.max_retries = max_retries
        self.session = None  # Session management for authenticated requests

     # get the date yesterday
    async def get_yesterday_date(self):
        yesterday = datetime.now() - timedelta(days=1)
        return yesterday.year, yesterday.month, yesterday.day
    
    # async def get_scraper(self):
    #     # Dictionary mapping platform names to their corresponding classes
    #     platform_scrapers = {
    #         'adcash': AdcashScraper,
    #         'adxad': AdxadScraper,
    #         'trafficstars': TrafficStarsAutomation,
    #         'trafficnomads': TrafficNomadsAutomation,
    #         'exoclick': ExoclickAutomation,
    #         'richads': RichadsAutomation,
    #         'daoad': DaoadAutomation,
    #         'clickadu': ClickAduAutomation
    #     }

    #     # Get the scraper class based on the platform name
    #     ScraperClass = platform_scrapers.get(self.platform)

    #     if ScraperClass:
    #         # Return an instance of the appropriate scraper class
    #         return ScraperClass(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
    #     else:
    #         raise ValueError(f"Unsupported platform: {self.platform}")
        
        
    async def fetch_info(self):
        data = {}
        scraper = None  # Initialize scraper to None
        # Parse the date string using strptime
        date_obj = datetime.strptime(self.targetdate, "%Y/%m/%d")
        try:
            logging.info(f"Platform: {self.platform}")
            if self.platform == 'adcash':
                ##date range completed
                scraper = AdcashScraper(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform, date_obj.day)
            
            elif self.platform == 'adxad':
                #date range completed
                # 09/19/2024
                # Parse the date string into a datetime object
                # Correct format for 'YYYY/MM/DD'
                date_obj_adc = datetime.strptime(self.targetdate, '%Y/%m/%d')
                # Reformat the date into a different format (for example, YYYY-MM-DD)
                formatted_date = date_obj_adc.strftime('%m/%d/%Y')
                scraper = AdxadScraper(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform, formatted_date)
            
            elif self.platform == 'trafficstars':
                # completed now
                # 09/18/2024
                # Step 1: Parse the date string into a datetime object
                date_obj_tra = datetime.strptime(self.targetdate, '%m/%d/%Y')

                # Step 2: Reformat the date into 'YYYY-MM-DD' format
                formatted_date_tra = date_obj_tra.strftime('%m/%d/%Y')
                scraper = TrafficStarsAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform, formatted_date_tra)
            
            elif self.platform == 'trafficnomads':
                #2024-09-17 - 2024-09-17 completed now
                date_obj_nomad = datetime.strptime(self.targetdate, '%Y/%m/%d')
                # Step 2: Reformat the date into 'YYYY-MM-DD' format
                formatted_date_nomad = date_obj_nomad.strftime('%Y-%m-%d')
                
                f_nomad_date = formatted_date_nomad + ' - ' + formatted_date_nomad
                scraper = TrafficNomadsAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform, f_nomad_date)
            
            elif self.platform == 'exoclick':
                scraper = ExoclickAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform)
                
            elif self.platform == 'richads':
                # Correct format for 'YYYY/MM/DD'
                date_obj_adc = datetime.strptime(self.targetdate, '%Y/%m/%d')
                # Reformat the date into a different format (for example, YYYY-MM-DD)
                formatted_date = date_obj_adc.strftime('%d.%m.%Y')

                # f_date = formatted_date + ' - ' + formatted_date
                scraper = RichadsAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform, formatted_date)
                
            elif self.platform == 'daoad':
                #date range completed
                #formatted date need to send 
                #2024-09-14 - 2024-09-14
                date_obj_dao = datetime.strptime(self.targetdate, '%Y/%m/%d')

                # Step 2: Reformat the date into 'YYYY-MM-DD' format
                formatted_date_dao = date_obj_dao.strftime('%Y-%m-%d')

                # Step 3: Create the range string 'YYYY-MM-DD - YYYY-MM-DD'
                f_date = formatted_date_dao + ' - ' + formatted_date_dao
                
                scraper = DaoadAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform, f_date)
            
            elif self.platform == 'clickadu':
                #date range completed
                scraper = ClickAduAutomation(self.keywords, self.email, self.password, self.link, self.creative_id, self.dashboard, self.platform, date_obj.day)
            # scraper = self.get_scraper()
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