from app.services.bo_service import TestAutomation

def test_test_login():
    test = TestAutomation("test@example.com", "password123")
    assert test.login() is None  # Adjust this based on your login method's return value

def test_test_fetch_data():
    test = TestAutomation("test@example.com", "password123")
    test.login()
    data = test.fetch_data()
    assert "message" in data
