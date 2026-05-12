from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.stock_service import train_stock_model, recommend_stock

router = APIRouter()

class TenantRequest(BaseModel):
    tenant_id: int

@router.post("/train")
def train(req: TenantRequest):
    result = train_stock_model(req.tenant_id)
    if not result["success"]:
        raise HTTPException(status_code=400, detail=result["error"])
    return result

@router.post("/recommend")
def recommend(req: TenantRequest):
    result = recommend_stock(req.tenant_id)
    if not result["success"]:
        raise HTTPException(status_code=400, detail=result["error"])
    return result