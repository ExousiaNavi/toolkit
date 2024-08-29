import os.path
from googleapiclient.discovery import build
from googleapiclient.errors import HttpError
from google.oauth2 import service_account
from datetime import datetime, timedelta

# Path to the service account key file
SERVICE_ACCOUNT_FILE = 'keys.json'
SCOPES = ["https://www.googleapis.com/auth/spreadsheets"]

# Initialize credentials and create a service object
creds = service_account.Credentials.from_service_account_file(
    SERVICE_ACCOUNT_FILE, scopes=SCOPES
)

SAMPLE_SPREADSHEET_ID = "1dkVVwuyLmvfpzYmhYVyCBK0XglN7SIc-jRMObmnt9w0"

def col_number_to_letter(col):
    """Convert a zero-based column number to an Excel-style letter representation."""
    letter = ""
    while col >= 0:
        letter = chr(col % 26 + ord('A')) + letter
        col = col // 26 - 1
    return letter

def yesterday_date(values):
    """Find the row index of yesterday's date."""
    yesterday_date = datetime.now() - timedelta(days=1)
    
    # Format yesterday's date as "Month Day" and "m/d/yyyy"
    formatted_date = yesterday_date.strftime("%B %d")  # e.g., "August 26"
    yesterday_date_no_zero = yesterday_date.strftime("%m/%d/%Y")  # e.g., "08/26/2024"

    # Search for the date in the sheet
    yesterdays_date_row_index = None
    for row_index, row in enumerate(values):
        if row and len(row) > 0:
            # Check the first element of the row for date formats
            row_date = row[0]
            
            # Compare both date formats
            if row_date == formatted_date or row_date == yesterday_date_no_zero:
                yesterdays_date_row_index = row_index
                break

    if yesterdays_date_row_index is not None:
        print(f"Yesterday's date '{yesterday_date_no_zero}' found in row {yesterdays_date_row_index + 1}")
        return yesterdays_date_row_index  # Return zero-based index
    else:
        print(f"Yesterday's date '{yesterday_date_no_zero}' not found.")
        return None

def find_and_get_values_below(sheet_id, sheet_name, target_substring):
    """Find the target substring, get values below in the same column, and update a cell."""
    try:
        service = build("sheets", "v4", credentials=creds)

        # Fetch all sheet data
        sheet = service.spreadsheets()
        result = sheet.values().get(
            spreadsheetId=sheet_id,
            range=f"{sheet_name}!A1:ZZ"
        ).execute()
        values = result.get("values", [])

        # print(result)
        if not values:
            print("No data found.")
            return

        # Search for the target substring and find its column index
        target_row_index = None
        target_col_index = None
        for row_index, row in enumerate(values):
            for col_index, cell in enumerate(row):
                if target_substring in cell:
                    target_row_index = row_index
                    target_col_index = col_index
                    break
            if target_row_index is not None:
                break

        if target_row_index is None or target_col_index is None:
            print(f"'{target_substring}' not found in the sheet.")
            return

        # Get all values in the target column below the target row
        column_values_below = []
        for row_index in range(target_row_index + 1, len(values)):
            if len(values[row_index]) > target_col_index:  # Ensure there is a column
                column_values_below.append(values[row_index][target_col_index])
        
        # Convert column index to letter representation
        col_letter = col_number_to_letter(target_col_index)
        print(f"Values in column '{col_letter}' below the row with '{target_substring}':")
        for value in column_values_below:
            print(value)

        # Find yesterday's date row index
        rowPositionYesterday = yesterday_date(values)
        if rowPositionYesterday is None:
            return

        print(f"Row position for yesterday: {rowPositionYesterday + 1}, Value on that spot: {values[rowPositionYesterday]}")

        # Construct the range in A1 notation for updating
        cell_range = f"{sheet_name}!{col_letter}{rowPositionYesterday + 1}"  # Adding 1 because rowPositionYesterday is zero-based
        
        # Prepare the body with values to update
        body = {
            "values": [
                [250, 250, '', 250, 250, '', '']  # Replace this with the actual values you want to update
            ]
        }

        # Update the cell in the sheet
        request = sheet.values().update(
            spreadsheetId=sheet_id,
            range=cell_range,
            valueInputOption="USER_ENTERED",
            body=body
        ).execute()

        print(request)
    except HttpError as err:
        print(err)

if __name__ == "__main__":
    find_and_get_values_below(SAMPLE_SPREADSHEET_ID, "Richads", "richlandrLSpk1")
