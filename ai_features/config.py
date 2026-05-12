import os
from dotenv import load_dotenv

load_dotenv()

DB_HOST     = os.getenv("DB_HOST", "postgres")
DB_PORT     = os.getenv("DB_PORT", "5432")
DB_NAME     = os.getenv("DB_NAME", "kitchenflow_db")
DB_USER     = os.getenv("DB_USER", "kitchenflow_user")
DB_PASSWORD = os.getenv("DB_PASSWORD", "secret")
GROQ_API_KEY = os.getenv("GROQ_API_KEY", "")

DATABASE_URL = f"postgresql://{DB_USER}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_NAME}"
MODELS_DIR   = "saved_models"