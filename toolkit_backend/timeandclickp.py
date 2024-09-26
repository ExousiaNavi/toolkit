# from datetime import datetime
# # Try parsing the date string in 'YYYY/MM/DD' format first
# # try:
# #     date_string = '2024-09-18'
# #     date_obj = datetime.strptime(date_string, '%Y/%m/%d')
# # except ValueError:
# #     # If the format is 'YYYY-MM-DD', try parsing that instead
# #     date_obj = datetime.strptime(date_string, '%Y-%m-%d')
    
# # # Reformat the date to 'M/D/YYYY' and remove leading zeros manually
# # formatted_date = date_obj.strftime('%m/%d/%Y').lstrip("0").replace("/0", "/")


# #2024-09-14 - 2024-09-14
# # Step 1: Parse the date string into a datetime object
# targetdate = '2024/09/20'
# # Correct format for 'YYYY/MM/DD'
# date_obj_adc = datetime.strptime(targetdate, '%Y/%m/%d')
# # Reformat the date into a different format (for example, YYYY-MM-DD)
# formatted_date = date_obj_adc.strftime('%d.%m.%Y')

# f_date = formatted_date + ' - ' + formatted_date
# print(f_date)

# from playwright.async_api import async_playwright

# async def run():
#     async with async_playwright() as p:
#         browser = await p.chromium.launch(headless=False)  # Keep the browser open for interaction
#         page = await browser.new_page()

#         # Navigate to the login page
#         await page.goto('https://example.com/login')

#         # Expose a Python function that will be called when the login button is clicked
#         def on_button_click():
#             print("Button manually clicked!")

#         await page.expose_function("python_callback", on_button_click)

#         # Inject JavaScript to detect when the login button is clicked
#         await page.evaluate("""
#             const loginButton = document.querySelector('#login-button-id');  # Replace with the actual ID
#             if (loginButton) {
#                 loginButton.addEventListener('click', () => {
#                     window.python_callback();  # Call the Python function when the button is clicked
#                 });
#             }
#         """)

#         # Wait for manual interaction (like clicking the login button)
#         await page.wait_for_timeout(60000)  # Keeps the browser open for 60 seconds
#         await browser.close()

# # Run the script
# import asyncio
# asyncio.run(run())

import os.path
import base64
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from googleapiclient.discovery import build
from googleapiclient.errors import HttpError
from email.mime.text import MIMEText

# Define the Gmail API scopes
SCOPES = ["https://www.googleapis.com/auth/gmail.send"]

def send_email(service, user_id, message):
    """Send an email message via Gmail API.
    Args:
        service: Authorized Gmail API service instance.
        user_id: User's email address or "me" to indicate authenticated user.
        message: Message to be sent (MIME message).
    Returns:
        Sent message details.
    """
    try:
        message = (service.users().messages().send(userId=user_id, body=message)
                   .execute())
        print(f"Message Id: {message['id']}")
        return message
    except HttpError as error:
        print(f"An error occurred: {error}")
        return None

def create_message(sender, to, subject, message_text):
    """Create a message to be sent as an email.
    Args:
        sender: Email address of the sender.
        to: Email address of the receiver.
        subject: Subject of the email.
        message_text: Body of the email.
    Returns:
        Encoded email message.
    """
    message = MIMEText(message_text)
    message['to'] = to
    message['from'] = sender
    message['subject'] = subject
    raw = base64.urlsafe_b64encode(message.as_bytes()).decode()
    return {'raw': raw}

def main():
    """Shows basic usage of the Gmail API.
    Sends an email using the Gmail API.
    """
    creds = None

    # Check if token.json already exists (stores user access tokens)
    if os.path.exists("client_secret.json"):
        creds = Credentials.from_authorized_user_file("client_secret.json", SCOPES)
    
    # If no valid credentials, prompt user to login and save them
    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            creds.refresh(Request())
        else:
            flow = InstalledAppFlow.from_client_secrets_file("client_secret.json", SCOPES)
            creds = flow.run_local_server(port=0)
        
        # Save the credentials for future use
        with open("client_secret.json", "w") as token:
            token.write(creds.to_json())

    try:
        # Build the Gmail API service
        service = build("gmail", "v1", credentials=creds)

        # Create the email message
        sender = "automation@auroramy.com"
        recipient = "exousia.navi@auroramy.com"
        subject = "Test Email from Gmail API"
        body = "This is a test email sent using the Gmail API in Python."
        
        message = create_message(sender, recipient, subject, body)

        # Send the email
        send_email(service, "me", message)

    except HttpError as error:
        print(f"An error occurred: {error}")

if __name__ == "__main__":
    main()




