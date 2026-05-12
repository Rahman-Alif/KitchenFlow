from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.affinity_service import get_affinity

router = APIRouter()

class TenantRequest(BaseModel):
    tenant_id: int

@router.post("")
def affinity(req: TenantRequest):
    result = get_affinity(req.tenant_id)
    if not result["success"]:
        raise HTTPException(status_code=400, detail=result["error"])
    return result