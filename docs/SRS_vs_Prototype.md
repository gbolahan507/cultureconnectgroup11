# SRS vs Prototype: What to Build

This document clarifies the difference between Oyin's full SRS and what we need for the prototype submission.

---

## Oyin's SRS (Full Production Vision)

Oyin created a comprehensive Software Requirements Specification covering a complete production system.

### User Types (3)
| User | Role |
|------|------|
| Resident | Browse, search, purchase, vote, give feedback |
| Creative SME | List products/services, process orders, manage bookings |
| Council Admin | Approve accounts/listings, manage areas, create polls, view analytics |

### Use Cases (22 total)
- **Residents (1-7):** Register, Login, Search, Browse, Purchase, Feedback, Vote
- **SMEs (8-13):** Register business, Create/Edit/Unlist listings, Process orders, Confirm bookings
- **Council (14-22):** Add areas, Approve accounts, Delete users, Create polls, View analytics

### Non-Functional Requirements
- Search queries < 2 seconds
- 100+ concurrent users
- Encrypted passwords
- Role-based access control
- 99% uptime
- Scalable design

---

## Prototype (What We Actually Build)

The marking scheme only requires a **working prototype** demonstrating core functionality.

### Basic Functional Requirements (40 marks)

| # | Feature | Marks |
|---|---------|-------|
| a | Add a new area | 3 |
| b | Add a new resident | 4 |
| c | Associate resident with area | 5 |
| d | Add new product to platform | 4 |
| e | Add vote by resident for product | 8 |
| f | Edit product/service data | 4 |
| g | Search/filter by category | 4 |
| h | Search/filter by category + price < £200 | 8 |

### Advanced Features (10 marks - Pick ONE)

| Option | Description |
|--------|-------------|
| Data Reporting | Ranked products by votes with name, price, score |
| Login System | Login screen + role-based page access |
| Batch Processing | Set product availability, restrict voting on unavailable |

---

## Side-by-Side Comparison

| Aspect | SRS (Full) | Prototype (Build This) |
|--------|------------|------------------------|
| User types | 3 (Resident, SME, Council) | 1-2 (Resident, optional Admin) |
| Use cases | 22 | 8 basic + 1 advanced |
| Account approval | Yes | No |
| Orders & purchases | Yes | No |
| Service bookings | Yes | No |
| Feedback & ratings | Yes | No |
| Analytics dashboard | Yes | No (unless chosen as advanced) |
| Performance | 100+ users, 99% uptime | Works on localhost |

---

## Database Comparison

### SRS Would Need
```
users (with roles)
sme_businesses
products (with approval status)
orders
bookings
feedback
votes
areas
polls
```

### Prototype Needs (What We Have)
```
areas
residents
platforms
products
votes
```

Our current database schema is sufficient for the marking scheme.

---

## Value of the SRS

The SRS is NOT wasted work:

1. **Team Report (10 marks)** - Shows thorough planning and requirements gathering
2. **Documentation** - Demonstrates professional software development process
3. **Future Reference** - If project were to expand beyond prototype
4. **Demo Talking Points** - Explain the full vision during presentation

---

## Recommendation

1. **Build:** Only the 8 basic features + 1 advanced feature
2. **Use SRS:** Reference in Team Report to show planning process
3. **Demo:** Present prototype, mention SRS shows full understanding

---

## Summary

| Document | Purpose |
|----------|---------|
| SRS | Shows what a full system would look like (great for report) |
| Prototype | What we actually build and submit (focus on marks) |

**Build simple. Document thoroughly. Get the marks.**
