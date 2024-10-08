from fastapi import APIRouter, HTTPException
from app.services.bo_service import BoAutomation
from app.services.fe_service import FeAutomation
from app.services.click_service import ClickAutomation
from app.services.spreedsheet_service import GoogleSheetsManager
from app.models.requests import TestRequest, FeRequest, ClicksAndImpressionRequest, SpreedSheetRequest
import logging

logger = logging.getLogger("my_logger")

router = APIRouter()
# routes for Fetching BO
@router.post("/fetch")
async def fetch_test_data(request: TestRequest):
    print(request)
    test = BoAutomation(request.email, request.password, request.link, request.currency, request.keyword, request.targetdate)
    # print(request.email)
    try:
        data = await test.fetch_bo()
        # data = await test.fetch_data()
        return {"data": data}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))

# routes for Fetching FE
@router.post("/data")
async def fetch_fe_data(request: FeRequest):
    print(request)
    fe_test = FeAutomation(request.username, request.password, request.link, request.currency, request.targetdate)
    # # print(request.email)
    try:
        data = await fe_test.fetch_fe()
        # data = await test.fetch_data()
        return {"data": data}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))
    
# routes for Fetching FE
@router.post("/clicks")
async def fetch_fe_data(request: ClicksAndImpressionRequest):
    #email='abiralmilan1014@gmail.com' password='B@j!qwe@4444' link='https://bajipartners.com/page/affiliate/login.jsp' creative_id=['20948', '20947', '22698']
    click_test = ClickAutomation(request.keywords, request.email, request.password, request.link, request.creative_id, request.dashboard, request.platform, request.targetdate)
    print(request)
    
    try:
        data = await click_test.fetch_info()
        print(f"Data: {data}")
        # data = await test.fetch_data()
        return {"data": data}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))
    
#generate report automatically
@router.post("/automate-spreedsheet")

async def automate_sheet(request: SpreedSheetRequest):
    results = [{
        "status": 200, 
        "text": "Automation Completed", 
        "title": "Spreadsheet List Automation Completed!", 
        "icon": "success"
    }]
    
    for item in request.request_data:
        try:
            spreadsheet_info = item['spreadsheet']
            target_date = item.get('target_date', None)
            spreed_id = spreadsheet_info['spreed_id']
            platform = spreadsheet_info['platform']
            keyword = item.get('keyword', None)  # Ensure keyword is fetched from the item
            if keyword == 'adcash':
                keyword = 'Baji (adcash)'
            if keyword == 'jbclickadubdt':
                keyword = 'Baji (jbclickadubdt)'
            if keyword == 'jbtrafficstars':
                keyword = 'Baji (jbtrafficstars)'
            if keyword == 's6clickadubdt':
                keyword = 'Six6s (s6clickadubdt)'
            bo_data = item['bo']
            # nsu = item['nsu']
            # ftd = item['ftd']

            # Initialize the GoogleSheetsManager for the general bo_data processing
            gmanager = GoogleSheetsManager(
                'keys.json',
                ["https://www.googleapis.com/auth/spreadsheets"],
                # for BO only
                spreed_id, platform, bo_data, keyword, target_date
            )
            data = await gmanager.run()  # Await the asynchronous run method
            results.append(data)

            # Check if 'impression_and_clicks' exists, is a list, and is not empty
            if 'impression_and_clicks' in item and isinstance(item['impression_and_clicks'], list):
                impression_and_clicks = item['impression_and_clicks']

                if impression_and_clicks:  # Check if the list is not empty
                    print("Processing impression and clicks data:")

                    # Iterate through the list of dictionaries
                    for record in impression_and_clicks:
                        b_o_s_id = record.get('b_o_s_id')
                        creative_id = record.get('creative_id')
                        imprs = record.get('imprs')
                        clicks = record.get('clicks')
                        spending = record.get('spending')
                        nsu = record.get('nsu')
                        ftd = record.get('ftd')

                        # Use the spreed_id and platform from the outer scope
                        gmanager = GoogleSheetsManager(
                            'keys.json',
                            ["https://www.googleapis.com/auth/spreadsheets"],
                            #for cost, impressions, clicks
                            spreed_id, platform, [spending, imprs, clicks, nsu, ftd], creative_id, target_date
                        )
                        data = await gmanager.run()  # Await the asynchronous run method
                        results.append(data)

                        print(f"b_o_s_id: {b_o_s_id}, creative_id: {creative_id}, imprs: {imprs}, clicks: {clicks}, spending: {spending}, nsu: {nsu}, ftd: {ftd}")

                else:
                    print("Impression and Clicks list is empty.")
            else:
                print("Key 'impression_and_clicks' not found or is not a list.")

        except Exception as e:
            logger.error(f"Error processing item: {item}, Error: {str(e)}")
            raise HTTPException(status_code=400, detail=str(e))
    print(f"Result: {results}")
    return {"data": results, "message": "Data processed successfully"}

    