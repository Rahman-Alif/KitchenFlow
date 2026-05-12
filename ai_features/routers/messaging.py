from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from services.groq_service import autofill_message

router = APIRouter()

class MessageRequest(BaseModel):
    content: str

@router.post("/autofill")
async def autofill(req: MessageRequest):
    result = await autofill_message(req.content)
    if not result["success"]:
        raise HTTPException(status_code=500, detail=result["error"])
    return result