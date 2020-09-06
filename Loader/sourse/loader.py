from datetime import datetime
from bs4 import BeautifulSoup
from database import DataBaseConnector
import requests
import pandas as pd
import json
import asyncio
import os


async def load_table_from_kurs() -> pd.DataFrame:
    """load_price_from_kurs Loads and parses a table from https://kurs.com.ua/

    Returns:
        pd.DataFrame: A dataframe with the table
    """

    url = "https://kurs.com.ua/"
    page = requests.get(url)
    soup = BeautifulSoup(page.text, "html.parser")
    script = soup.find("script", type="application/ld+json")
    json_data = json.loads(script.string)
    columns = json_data["mainEntity"]["csvw:tableSchema"]["csvw:columns"]

    data = [[cell["csvw:value"] for cell in column["csvw:cells"]] for column in columns]
    table = pd.DataFrame(
        {f"Col_{i+1}": column for i, column in enumerate(data[1:])}, index=data[0]
    )

    return table


async def load_UAHUSD_price() -> float:
    """load_EURUSD_price Loads a UAHUSD price from https://kurs.com.ua/

    Returns:
        float: Current UAHUSD price
    """

    return round(float((await load_table_from_kurs()).loc["USD", "Col_3"]), 2)


async def load_EURUSD_price() -> float:
    """load_EURUSD_price Loads a EURUSD price from https://www.freeforexapi.com/

    Returns:
        float: Current EURUSD price
    """

    url = "https://www.freeforexapi.com/api/live?pairs=EURUSD"
    page = requests.get(url)

    data = page.json()
    return round(float(data["rates"]["EURUSD"]["rate"]), 3)


async def load_BTCUSDT_price() -> float:
    """load_BTCUSDT_price Loads a EURUSD price from http://api.binance.com/

    Returns:
        float: Current BTCUSDT price
    """

    data = requests.get(
        f"http://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT"
    ).json()
    return round(float(data["price"]))


async def load_XAUUSD_price() -> float:
    """load_XAUUSD_price Loads a XAUUSD price from https://forex-data-feed.swissquote.com/

    Returns:
        float: Current XAUUSD price divided by 31.1
    """

    data = requests.get(
        "https://forex-data-feed.swissquote.com/public-quotes/bboquotes/instrument/XAU/USD"
    ).json()
    info = data[0]["spreadProfilePrices"][0]
    return round((float(info["bid"]) + float(info["ask"])) / 2 / 31.1, 3)


async def main():
    try:
        conn = await DataBaseConnector.connect()
    except Exception as e:
        print("\n----- Critical -----")
        print("An error occured while trying to connect to database.")
        print("Configure MySQL server data in config.ini or double check the info.")
        print(f"\nAdditinal data: [{e.__class__.__name__}] {e}")
        return

    while True:
        print("Loading price data...")
        try:
            values = await asyncio.gather(
                load_UAHUSD_price(),
                load_EURUSD_price(),
                load_XAUUSD_price(),
                load_BTCUSDT_price(),
            )
        except Exception as e:
            print(
                f"Error: could not load the data, trying again in 30 seconds \nAdditional data: [{e.__class__.__name__}] {e}"
            )
            await asyncio.sleep(30)
        else:
            for pair, value in zip(["UAHUSD", "EURUSD", "XAUUSD", "BTCUSDT"], values):
                await conn.add_data(pair, value)  # type: ignore
                print(f"    Added {pair} price: {value}")

            print(f"Finished! Waiting 5 minutes...")

            await asyncio.sleep(5 * 60)


if __name__ == "__main__":
    asyncio.run(main())
    os.system("pause")
