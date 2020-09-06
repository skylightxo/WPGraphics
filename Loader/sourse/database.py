from __future__ import annotations

import aiomysql
import asyncio
import typing
import configparser


class DataBaseConnector:
    async def _init(self):
        self.conn = await self.init_database()

    async def add_data(
        self,
        pair: typing.Literal["UAHUSD", "XAUUSD", "BTCUSDT", "EURUSD"],
        value: float,
    ) -> None:
        async with self.conn.cursor() as cur:
            # NOTE: SQL Injections not possible due to not using input data
            sql = f"INSERT INTO `{pair}`(`value`) VALUES ('{round(value, 3)}')"
            await cur.execute(sql)
        await self.conn.commit()

    @staticmethod
    async def init_database() -> aiomysql.Connection:
        """init_database Connects to the MySQL Database and initialise it.

        Will use data stored in config.ini file

        Returns:
            aiomysql.Connection: A new database connection
        """

        config = configparser.ConfigParser()
        config.read("config.ini")
        dbinfo = config["MySQL"]

        conn = await aiomysql.connect(
            host=dbinfo["host"],
            port=int(dbinfo["port"]),
            user=dbinfo["user"],
            password=dbinfo["password"],
            db="mysql",
            loop=asyncio.get_event_loop(),
        )

        async with conn.cursor() as cur:
            await cur.execute("CREATE DATABASE IF NOT EXISTS `courses`")
            await cur.execute("USE `courses`")
            await cur.execute(
                "CREATE TABLE IF NOT EXISTS `UAHUSD`("
                "   `id` int NOT NULL AUTO_INCREMENT,"
                "   `value` float NOT NULL,"
                "   `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                "   PRIMARY KEY(`id`)"
                ")"
            )
            await cur.execute(
                "CREATE TABLE IF NOT EXISTS `EURUSD`("
                "   `id` int NOT NULL AUTO_INCREMENT,"
                "   `value` float NOT NULL,"
                "   `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                "   PRIMARY KEY(`id`)"
                ")"
            )
            await cur.execute(
                "CREATE TABLE IF NOT EXISTS `BTCUSDT`("
                "   `id` int NOT NULL AUTO_INCREMENT,"
                "   `value` float NOT NULL,"
                "   `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                "   PRIMARY KEY(`id`)"
                ")"
            )
            await cur.execute(
                "CREATE TABLE IF NOT EXISTS `XAUUSD`("
                "   `id` int NOT NULL AUTO_INCREMENT,"
                "   `value` float NOT NULL,"
                "   `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                "   PRIMARY KEY(`id`)"
                ")"
            )

        return conn

    @staticmethod
    async def connect() -> DataBaseConnector:
        res = DataBaseConnector()
        await res._init()
        return res
