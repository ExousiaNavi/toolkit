from pydantic import BaseModel

class TestRequest(BaseModel):
    email: str
    password: str

# class TwitterRequest(BaseModel):
#     email: str
#     password: str

# class InstagramRequest(BaseModel):
#     email: str
#     password: str
