from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.groq_service import generate_description

router = APIRouter()

class DescriptionRequest(BaseModel):
    category_name: str
    item_name: str

@router.post("/generate")
async def generate(req: DescriptionRequest):
    result = await generate_description(req.category_name, req.item_name)
    if not result["success"]:
        raise HTTPException(status_code=500, detail=result["error"])
    return result