from fastapi import APIRouter, HTTPException
from app.services.test_service import TestAutomation
from app.models.requests import TestRequest

router = APIRouter()

@router.post("/fetch")
def fetch_test_data(request: TestRequest):
    test = TestAutomation(request.email, request.password)
    try:
        test.login()
        data = test.fetch_data()
        return {"data": data}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))
