# Creator X Profile Content Type – Official Setup
### RareImagery X Marketplace
**Live site:** http://72.62.80.155:8080

> **Why we do this:** Commerce Stores are kept clean. All X branding data (PFP, banner, top posts, followers) goes in this content type and links back to the Store. Grok fills it automatically.

---

## Step 1: Create the Content Type (~2 minutes)

1. Go to **Structure → Content types → Add content type**
2. **Name:** `Creator X Profile`
3. **Machine name:** `creator_x_profile` *(auto-filled)*
4. **Description:** `X profile data linked to each Creator Store – auto-populated by Grok`
5. Save and keep the default settings.

---

## Step 2: Add the Fields (~5 minutes)

Go to **Manage fields** for the new content type and add these exact fields:

| Field Label | Type | Notes |
|---|---|---|
| X Username | Text (plain) | Required |
| Profile Picture (PFP) | Image | Allowed: `png, jpg, jpeg` · Directory: `creator-pfps` |
| Background Banner | Image | Same settings as PFP |
| Bio / Description | Text (formatted, long) | — |
| Follower Count | Number (integer) | — |
| Top 8 Posts | JSON or Long Text | Multiple values allowed — Grok stores full post data |
| Top 8 Followers | JSON | Multiple values — usernames + avatar URLs |
| Metrics *(optional)* | JSON | Engagement scores calculated by Grok |
| **Linked Store** | **Entity Reference** | See details below ↓ |

### Linked Store Field — Exact Settings

This is the critical connection between the X Profile and the Commerce Store.

- **Type:** Entity Reference
- **Target type:** Commerce Store
- **Target bundle:** Creator Store *(or your default store type)*
- **Number of values:** 1
- **Widget:** Select list or Autocomplete
- **Optional:** Check *"Create referenced entity if it does not yet exist"*

Save each field after adding it.

---

## Step 3: Make the Link Bidirectional *(recommended)*

On the **Commerce Store** entity:

**Commerce → Configuration → Store types → Manage fields**

Add an Entity Reference field called **Linked X Profile** pointing back to `Creator X Profile`.

This gives you a clean two-way connection between every Store and its X Profile.

---

## Step 4: Grok Auto-Fills Everything

Once the AI module is configured, a one-click **"Import X Profile with Grok"** button will be added to the Store creation form.

It will:
- Pull live X data for the creator
- Create the `Creator X Profile` node automatically
- Link it to the Store instantly

---

## Next Steps

Choose what to ship next:

- **Build the Grok AI Operation** for 1-click X import *(exact config)*
- **Add the Import button** to the Store creation form
- **Create a sample Creator Store + Profile** *(test data)*
- **Set up Views** so admins see the linked Store + Profile together
- **Set up subdomain wildcard** on Vercel (`creatorname.rareimagery.net`)

---

> Every X creator now gets a fully branded store where their followers buy products — with Grok handling the data import automatically.
