from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from routers import revenue, demand, affinity, stock, description, messaging

app = FastAPI(title="KitchenFlow AI Features", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:3000"],
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(revenue.router,     prefix="/revenue",     tags=["Revenue Forecast"])
app.include_router(demand.router,      prefix="/demand",      tags=["Item Demand"])
app.include_router(affinity.router,    prefix="/affinity",    tags=["Affinity Analysis"])
app.include_router(stock.router,       prefix="/stock",       tags=["Stock Recommendation"])
app.include_router(description.router, prefix="/description", tags=["Description Autofill"])
app.include_router(messaging.router,   prefix="/messaging",   tags=["Message Autofill"])

@app.get("/health")
def health():
    return {"status": "ok"}