from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.insight_service import gather_insight_context
from services.groq_service import generate_insight

router = APIRouter()

class TenantRequest(BaseModel):
    tenant_id: int

@router.post("")
async def insight(req: TenantRequest):
    try:
        context = gather_insight_context(req.tenant_id)
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Data error: {str(e)}")

    result = await generate_insight(context)
    if not result["success"]:
        raise HTTPException(status_code=500, detail=result["error"])

    return {"success": True, "insight": result["insight"], "context": context}