from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.demand_service import train_demand_model, forecast_demand

router = APIRouter()

class TenantRequest(BaseModel):
    tenant_id: int

@router.post("/train")
def train(req: TenantRequest):
    result = train_demand_model(req.tenant_id)
    if not result["success"]:
        raise HTTPException(status_code=400, detail=result["error"])
    return result

@router.post("/forecast")
def forecast(req: TenantRequest):
    result = forecast_demand(req.tenant_id)
    if not result["success"]:
        raise HTTPException(status_code=400, detail=result["error"])
    return result