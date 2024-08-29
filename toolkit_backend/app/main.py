from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import asyncio
import sys
from app.routes import test

if sys.platform == 'win32':
    asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
    
app = FastAPI()

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://10.1.55.79:8000"],  # Allow specific origins (your Laravel app's URL)
    allow_credentials=True,
    allow_methods=["*"],  # Allow all HTTP methods (GET, POST, etc.)
    allow_headers=["*"],  # Allow all headers
)
# Include platform-specific routes
app.include_router(test.router, prefix="/api/bo")
app.include_router(test.router, prefix="/api/fe")
app.include_router(test.router, prefix="/api/cli")
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


# if __name__ == "__main__":
#     import uvicorn
#     uvicorn.run(app, host="127.0.0.1", port=8081)