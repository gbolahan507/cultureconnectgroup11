# CultureConnect UI Flow

Visual guide for the prototype user interface.

---

## Main Navigation

```
┌─────────────────────────────────────────────────────────────┐
│                    CULTURECONNECT                            │
│  [Home]  [Areas]  [Residents]  [Products]  [Votes]  [Login] │
└─────────────────────────────────────────────────────────────┘
```

---

## Page Flow Diagram

```
                         ┌──────────────┐
                         │   HOME       │
                         │  (Dashboard) │
                         └──────┬───────┘
                                │
        ┌───────────┬───────────┼───────────┬───────────┐
        ▼           ▼           ▼           ▼           ▼
   ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐
   │  AREAS  │ │RESIDENTS│ │PRODUCTS │ │  VOTES  │ │  LOGIN  │
   │  List   │ │  List   │ │  List   │ │ Summary │ │  Form   │
   └────┬────┘ └────┬────┘ └────┬────┘ └─────────┘ └─────────┘
        │           │           │
        ▼           ▼           ▼
   ┌─────────┐ ┌─────────┐ ┌─────────┐
   │Add Area │ │Add Resid│ │Add Prod │
   │  Form   │ │  Form   │ │  Form   │
   └─────────┘ └────┬────┘ └────┬────┘
                    │           │
                    ▼           ▼
              ┌──────────┐ ┌─────────┐
              │Select    │ │Edit Prod│
              │Area      │ │  Form   │
              └──────────┘ └─────────┘
```

---

## Page Wireframes

### 1. HOME (Dashboard)

```
┌────────────────────────────────────────────────────┐
│  Welcome to CultureConnect                         │
│                                                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐         │
│  │ 5 Areas  │  │ 12 Resid │  │ 16 Prods │         │
│  └──────────┘  └──────────┘  └──────────┘         │
│                                                    │
│  Popular Products          Recent Votes            │
│  ─────────────────        ─────────────            │
│  1. Pottery (8 votes)     John → Pottery           │
│  2. Dance Class (6)       Sarah → Dance            │
│  3. Spice Box (5)         Mike → Basket            │
└────────────────────────────────────────────────────┘
```

**Purpose:** Overview of the platform with quick stats.

---

### 2. AREAS PAGE (3 marks)

**List View:**
```
┌────────────────────────────────────────────────────┐
│  Areas                           [+ Add Area]      │
│                                                    │
│  ┌──────────────────────────────────────────────┐ │
│  │ ID │ Name           │ Actions                │ │
│  ├────┼────────────────┼────────────────────────┤ │
│  │ 1  │ North London   │ [Edit] [Delete]        │ │
│  │ 2  │ South London   │ [Edit] [Delete]        │ │
│  │ 3  │ East London    │ [Edit] [Delete]        │ │
│  └──────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

**Add Form:**
```
┌────────────────────────────────────────────────────┐
│  Add New Area                                      │
│                                                    │
│  Area Name: [____________________]                 │
│                                                    │
│  [Save]  [Cancel]                                  │
└────────────────────────────────────────────────────┘
```

---

### 3. RESIDENTS PAGE (4 + 5 marks)

**List View:**
```
┌────────────────────────────────────────────────────┐
│  Residents                      [+ Add Resident]   │
│                                                    │
│  ┌──────────────────────────────────────────────┐ │
│  │ Name         │ Email          │ Area         │ │
│  ├──────────────┼────────────────┼──────────────┤ │
│  │ John Smith   │ john@email.com │ North London │ │
│  │ Sarah Jones  │ sarah@mail.com │ South London │ │
│  └──────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

**Add Form (with Area Selection):**
```
┌────────────────────────────────────────────────────┐
│  Add New Resident                                  │
│                                                    │
│  Name:     [____________________]                  │
│  Email:    [____________________]                  │
│  Password: [____________________]                  │
│  Area:     [Select Area ▼]        ← Links to area │
│  Age:      [Select ▼]                              │
│  Gender:   [Select ▼]                              │
│                                                    │
│  [Register]  [Cancel]                              │
└────────────────────────────────────────────────────┘
```

**Note:** Area dropdown links resident to an area (5 marks).

---

### 4. PRODUCTS PAGE (4 + 4 + 8 marks)

**List View with Filters:**
```
┌────────────────────────────────────────────────────┐
│  Products                        [+ Add Product]   │
│                                                    │
│  Filter: [Category ▼] [Price < £200 ☐]  [Search]  │
│                                                    │
│  ┌──────────────────────────────────────────────┐ │
│  │ Name          │ Price │ Category │ Platform  │ │
│  ├───────────────┼───────┼──────────┼───────────┤ │
│  │ Pottery       │ £75   │ Art      │ African.. │ │
│  │ Dance Class   │ £30   │ Experience│ Heritage.│ │
│  │ Wooden Sculpt │ £250  │ Art      │ African.. │ │
│  └──────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

**Add/Edit Form:**
```
┌────────────────────────────────────────────────────┐
│  Add/Edit Product                                  │
│                                                    │
│  Name:        [____________________]               │
│  Description: [____________________]               │
│  Price (£):   [________]                           │
│  Category:    [Select ▼]                           │
│  Platform:    [Select ▼]                           │
│                                                    │
│  [Save]  [Cancel]                                  │
└────────────────────────────────────────────────────┘
```

**Filter Features:**
- Category dropdown (4 marks)
- Price < £200 checkbox (8 marks)

---

### 5. VOTING PAGE (8 marks)

```
┌────────────────────────────────────────────────────┐
│  Vote for Products                                 │
│                                                    │
│  Resident: [Select Resident ▼]                     │
│                                                    │
│  ┌─────────────────────────────────────────────┐  │
│  │  ┌───────────┐  ┌───────────┐  ┌──────────┐ │  │
│  │  │ [Image]   │  │ [Image]   │  │ [Image]  │ │  │
│  │  │ Pottery   │  │ Dance     │  │ Spices   │ │  │
│  │  │ £75       │  │ £30       │  │ £40      │ │  │
│  │  │ ⭐ 8 votes│  │ ⭐ 6 votes│  │ ⭐ 5 vote│ │  │
│  │  │ [Vote]    │  │ [Vote]    │  │ [Vote]   │ │  │
│  │  └───────────┘  └───────────┘  └──────────┘ │  │
│  └─────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────┘
```

**Flow:**
1. Select resident from dropdown
2. Browse products
3. Click "Vote" on a product
4. Vote is recorded

---

### 6. LOGIN PAGE (Optional - 10 marks advanced)

```
┌────────────────────────────────────────────────────┐
│                                                    │
│              ┌─────────────────────┐               │
│              │   CULTURECONNECT    │               │
│              │                     │               │
│              │  Email:             │               │
│              │  [________________] │               │
│              │                     │               │
│              │  Password:          │               │
│              │  [________________] │               │
│              │                     │               │
│              │  [Login]            │               │
│              │                     │               │
│              │  Don't have account?│               │
│              │  [Register]         │               │
│              └─────────────────────┘               │
│                                                    │
└────────────────────────────────────────────────────┘
```

---

## User Flow Summary

```
1. Admin adds AREA → "North London"
         ↓
2. Resident REGISTERS → Selects "North London" as their area
         ↓
3. Admin adds PRODUCT → "Pottery, £75, Art category"
         ↓
4. Resident VOTES → Selects "Pottery"
         ↓
5. Anyone can FILTER → "Show Art products under £200"
         ↓
6. Admin can EDIT → Change "Pottery" price to £80
```

---

## Pages to Marks Mapping

| Page | Features | Marks |
|------|----------|-------|
| Areas | Add area | 3 |
| Residents | Add resident | 4 |
| Residents | Select area (dropdown) | 5 |
| Products | Add product | 4 |
| Products | Edit product | 4 |
| Products | Filter by category | 4 |
| Products | Filter by price < £200 | 8 |
| Votes | Vote on product | 8 |
| **Total** | | **40** |
| Login | (Optional advanced) | 10 |

---

## File Structure (Suggested)

```
pages/
├── index.php           → Home/Dashboard
├── areas.php           → List areas
├── add_area.php        → Add area form
├── residents.php       → List residents
├── add_resident.php    → Add resident form
├── products.php        → List products (with filters)
├── add_product.php     → Add product form
├── edit_product.php    → Edit product form
├── vote.php            → Voting page
└── login.php           → Login page (optional)
```

---

## Bootstrap Components to Use

| Component | Use For |
|-----------|---------|
| Navbar | Main navigation |
| Cards | Product display, stats |
| Tables | List views (areas, residents, products) |
| Forms | All input forms |
| Buttons | Actions (Save, Cancel, Vote) |
| Dropdowns | Filters, selects |
| Alerts | Success/error messages |

---

## Notes for Frontend Developer (Josephine)

1. Use Bootstrap 5 for all styling
2. Keep forms simple and clean
3. Use cards for product display on voting page
4. Tables for list views
5. Clear navigation between pages
6. Form validation with meaningful error messages (5 marks for UI)
