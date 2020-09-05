from datetime import datetime
from bs4 import BeautifulSoup
from database import DataBaseConnector
import requests
import pandas as pd
import json
import asyncio


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

    return float((await load_table_from_kurs()).loc["USD", "Col_3"])


async def load_EURUSD_price() -> float:
    """load_EURUSD_price Loads a EURUSD price from https://www.freeforexapi.com/

    Returns:
        float: Current EURUSD price
    """

    url = "https://www.freeforexapi.com/api/live?pairs=EURUSD"
    page = requests.get(url)

    data = page.json()
    return float(data["rates"]["EURUSD"]["rate"])


async def load_BTCUSDT_price() -> float:
    """load_BTCUSDT_price Loads a EURUSD price from http://api.binance.com/

    Returns:
        float: Current BTCUSDT price
    """

    data = requests.get(
        f"http://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT"
    ).json()
    return float(data["price"])


async def load_XAUUSD_price() -> float:
    """load_XAUUSD_price Loads a XAUUSD price from https://forex-data-feed.swissquote.com/

    Returns:
        float: Current XAUUSD price divided by 31.1
    """

    data = requests.get(
        "https://forex-data-feed.swissquote.com/public-quotes/bboquotes/instrument/XAU/USD"
    ).json()
    info = data[0]["spreadProfilePrices"][0]
    return (float(info["bid"]) + float(info["ask"])) / 2 / 31.1


async def main():
    conn = await DataBaseConnector.connect()

    while True:
        values = await asyncio.gather(
            load_UAHUSD_price(),
            load_EURUSD_price(),
            load_XAUUSD_price(),
            load_BTCUSDT_price(),
        )

        for pair, value in zip(["UAHUSD", "EURUSD", "XAUUSD", "BTCUSDT"], values):
            await conn.add_data(pair, value)  # type: ignore

        await asyncio.sleep(5 * 60)


if __name__ == "__main__":
    asyncio.run(main())
