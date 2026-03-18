import anthropic
import sys

client = anthropic.Anthropic(
    # Set ANTHROPIC_API_KEY env var, or replace below
    # api_key="sk-ant-..."
)

# Upload a file (optional)
if len(sys.argv) > 1:
    file_path = sys.argv[1]
    print(f"Uploading {file_path}...")
    uploaded_file = client.files.create(
        file=open(file_path, "rb"),
    )
    print(f"File uploaded: {uploaded_file.id}")

    # Use the file in a message
    message = client.messages.create(
        model="claude-sonnet-4-6",
        max_tokens=20000,
        temperature=1,
        messages=[
            {
                "role": "user",
                "content": [
                    {
                        "type": "file",
                        "source": {
                            "type": "file",
                            "file_id": uploaded_file.id,
                        },
                    },
                    {
                        "type": "text",
                        "text": "Summarize this document.",
                    },
                ],
            }
        ],
    )
else:
    # Simple message without file
    message = client.messages.create(
        model="claude-sonnet-4-6",
        max_tokens=20000,
        temperature=1,
        messages=[
            {
                "role": "user",
                "content": "Hello, Claude!",
            }
        ],
    )

print(message.content[0].text)
