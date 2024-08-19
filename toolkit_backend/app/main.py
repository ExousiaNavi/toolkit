from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.routes import test

app = FastAPI()

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://127.0.0.1:8000"],  # Allow specific origins (your Laravel app's URL)
    allow_credentials=True,
    allow_methods=["*"],  # Allow all HTTP methods (GET, POST, etc.)
    allow_headers=["*"],  # Allow all headers
)
# Include platform-specific routes
app.include_router(test.router, prefix="/api/test")
# app.include_router(twitter.router, prefix="/api/twitter")
# app.include_router(instagram.router, prefix="/api/instagram")

@app.get("/")
def read_root():
    data = {
        "status": 200,
        "text": "You have successfully connected to the server. Everything is up and running smoothly.",
        "title": "Connection Established!",
        "icon": "success",
        "data": [],
    }

    return data
