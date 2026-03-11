# Creator X Profile Content Type – Official Setup (RareImagery X Marketplace)
Live site: http://72.62.80.155
**Why we do this:** Commerce Stores are kept clean. All X branding data (PFP, banner, top posts, followers) goes here and links back to the Store. Grok fills it automatically.

## Step 1: Create the Content Type (2 minutes)
1. Go to **Structure → Content types → Add content type**  
2. Name: `Creator X Profile`  
3. Machine name: `creator_x_profile` (auto-filled)  
4. Description: “X profile data linked to each Creator Store – auto-populated by Grok”  
5. Save and keep the default settings.

## Step 2: Add the Fields (5 minutes)
Go to **Manage fields** for the new content type and add these exact fields:

- **X Username**  
  Type: Text (plain) → Required  

- **Profile Picture (PFP)**  
  Type: Image → Allowed extensions: png, jpg, jpeg → Directory: creator-pfps  

- **Background Banner**  
  Type: Image → Same settings as above  

- **Bio / Description**  
  Type: Text (formatted, long)  

- **Follower Count**  
  Type: Number (integer)  

- **Top 8 Posts**  
  Type: JSON or Long Text → Multiple values allowed (Grok will store full post data here)  

- **Top 8 Followers**  
  Type: JSON → Multiple values (usernames + avatar URLs)  

- **Metrics** (optional extra)  
  Type: JSON (for engagement scores Grok calculates)  

- **Linked Store** ← THIS IS THE CONNECTION  
  Type: Entity Reference → Target type: Commerce Store  
  → Target bundle: Creator Store (or your default store type)  
  → Number of values: 1  
  → Check “Create referenced entity if it does not yet exist” (optional)  
  → Widget: Select list or Autocomplete  

Save each field.

## Step 3: Make the Link Bidirectional (optional but recommended)
On the **Commerce Store** entity (Commerce → Configuration → Store types → Manage fields):  
Add an Entity Reference field called **Linked X Profile** that points back to Creator X Profile.  
This gives you perfect two-way connection.

## Step 4: Grok Will Auto-Fill Everything
Once the AI module is set up, we’ll create a one-click “Import X Profile with Grok” button on the Store creation form.  
It will:
- Pull real X data
- Create the Creator X Profile node
- Link it to the Store automatically

## Next Steps (pick one and reply)
Your first Creator Store can now have full X branding in seconds.

Reply with any of these and I’ll drop the exact next piece:
- “Build the Grok AI Operation for 1-click X import” (exact config)  
- “Add the Import button to Store creation form”  
- “Create sample Creator Store + Profile” (test data)  
- “Set up Views so admins see linked Store + Profile together”  
- “Ready for subdomain wildcard on Vercel”

We just gave every X creator the perfect branded store where their followers buy products.  
Grok is about to make the magic happen.

What are we shipping first? 🔥