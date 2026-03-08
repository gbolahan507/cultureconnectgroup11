# CultureConnect Use Cases

Based on Oyin's SRS Document (22 Use Cases)

---

## Residents (Use Cases 1-7)

| # | Use Case | Description | Required for Prototype? |
|---|----------|-------------|------------------------|
| 1 | Register account | Resident creates account with details | Yes (Add resident) |
| 2 | Login | Resident logs into system | Optional (Advanced) |
| 3 | Search products/services | Find products by keyword | Yes (Filter) |
| 4 | Browse products/services | View all available products | Yes (Display) |
| 5 | Purchase products | Buy products from SMEs | No |
| 6 | Submit feedback & ratings | Rate purchased products | No |
| 7 | Vote on cultural offerings | Vote for favorite products | Yes (8 marks) |

---

## Creative SMEs (Use Cases 8-13)

| # | Use Case | Description | Required for Prototype? |
|---|----------|-------------|------------------------|
| 8 | Register business account | SME creates account (needs approval) | No |
| 9 | Create product listings | Add new products to platform | Yes (Add product) |
| 10 | Edit product listings | Modify existing products | Yes (4 marks) |
| 11 | Unlist products | Remove products from display | No |
| 12 | Process orders | Handle customer purchases | No |
| 13 | Confirm service bookings | Accept booking requests | No |

---

## Council Admin (Use Cases 14-22)

| # | Use Case | Description | Required for Prototype? |
|---|----------|-------------|------------------------|
| 14 | Add cultural areas | Create new area/location | Yes (3 marks) |
| 15 | Approve resident accounts | Verify resident registrations | No |
| 16 | Approve SME accounts | Verify business registrations | No |
| 17 | Approve SME listings | Verify product listings | No |
| 18 | Delete user accounts | Remove users from system | No |
| 19 | Create voting polls | Set up new votes | No |
| 20 | View voting analytics | See voting statistics | Optional (Advanced) |
| 21 | Add council members | Create admin accounts | No |
| 22 | Manage platform settings | Configure system options | No |

---

## Summary: What to Build

### Must Have (40 marks)

| Prototype Feature | Related Use Case | Marks |
|-------------------|------------------|-------|
| Add area | UC 14 | 3 |
| Add resident | UC 1 | 4 |
| Link resident to area | UC 1 | 5 |
| Add product | UC 9 | 4 |
| Vote on product | UC 7 | 8 |
| Edit product | UC 10 | 4 |
| Filter by category | UC 3-4 | 4 |
| Filter by price < £200 | UC 3-4 | 8 |

### Pick ONE Advanced (10 marks)

| Option | Related Use Case |
|--------|------------------|
| Login system | UC 2 |
| Data reporting (ranked votes) | UC 20 |
| Batch processing (availability) | UC 11 |

---

## Use Cases NOT Needed for Submission

| Use Case | Reason |
|----------|--------|
| UC 5 - Purchase | Not in marking scheme |
| UC 6 - Feedback | Not in marking scheme |
| UC 8 - SME registration | Not in marking scheme |
| UC 12 - Process orders | Not in marking scheme |
| UC 13 - Service bookings | Not in marking scheme |
| UC 15-18 - Approvals | Not in marking scheme |
| UC 19 - Voting polls | Not in marking scheme |
| UC 21-22 - Admin management | Not in marking scheme |

---

## Visual: 22 Use Cases → 8 Needed

```
RESIDENTS (7)          SMEs (6)              COUNCIL (9)
─────────────          ────────              ───────────
✓ 1. Register          ✗ 8. Register SME     ✓ 14. Add area
? 2. Login             ✓ 9. Add product      ✗ 15. Approve residents
✓ 3. Search            ✓ 10. Edit product    ✗ 16. Approve SMEs
✓ 4. Browse            ✗ 11. Unlist          ✗ 17. Approve listings
✗ 5. Purchase          ✗ 12. Process orders  ✗ 18. Delete users
✗ 6. Feedback          ✗ 13. Bookings        ✗ 19. Create polls
✓ 7. Vote                                    ? 20. Analytics
                                             ✗ 21. Add council
                                             ✗ 22. Settings

✓ = Required    ? = Optional (Advanced)    ✗ = Not needed
```

---

## Conclusion

- **SRS has 22 use cases** - Great for documentation
- **Prototype needs 8** - Focus on these for marks
- **Oyin's work is valuable** - Reference in Team Report
