from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.revenue_service import train_revenue_model, forecast_revenue

router = APIRouter()

class TenantRequest(BaseModel):
    tenant_id: int

@router.post("/train")
def train(req: TenantRequest):
    result = train_revenue_model(req.tenant_id)
    if not result["success"]:
        raise HTTPException(status_code=400, detail=result["error"])
    return result

@router.post("/forecast")
def forecast(req: TenantRequest):
    result = forecast_revenue(req.tenant_id)
    if not result["success"]:
        raise HTTPException(status_code=400, detail=result["error"])
    return result