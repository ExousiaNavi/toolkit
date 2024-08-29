from pydantic import BaseModel
from typing import List, Dict, Any
class TestRequest(BaseModel):
    email: str
    password: str
    link: str
    currency: str
    keyword: List[str]  # Expecting a list of strings
    
    
class FeRequest(BaseModel):
    username: str
    password: str
    link: str
    currency: str
    
    
class ClicksAndImpressionRequest(BaseModel):
    keywords: str
    email: str
    password: str
    link: str
    creative_id: List[str]
    dashboard: str
    platform: str
    
class SpreedSheetRequest(BaseModel):
    request_data: List[Dict[str, Any]]