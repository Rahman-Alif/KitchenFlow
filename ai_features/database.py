from sqlalchemy import create_engine, text
from config import DATABASE_URL

# Read-only engine — AI features never write to the DB
engine = create_engine(DATABASE_URL, pool_pre_ping=True)

def get_connection():
    return engine.connect()