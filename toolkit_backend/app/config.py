import os
from dotenv import load_dotenv

load_dotenv()  # Load environment variables from .env file

FACEBOOK_API_KEY = os.getenv("FACEBOOK_API_KEY", "default_key")
