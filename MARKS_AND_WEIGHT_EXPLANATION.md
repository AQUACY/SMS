# Understanding Marks and Weight in Exams/Assessments

## Overview

When creating an exam or assessment, you need to provide two important values:
1. **Total Marks** - The maximum score for that specific assessment
2. **Weight** - The percentage contribution to the final term grade

These are **independent** values that serve different purposes.

---

## 1. Total Marks (Maximum Score)

### What it is:
- The **maximum possible score** a student can achieve in this specific assessment
- This is the "out of" value (e.g., "out of 100", "out of 50")

### Examples:
- **Final Exam**: 100 marks (students can score 0-100)
- **Mid-Term Exam**: 50 marks (students can score 0-50)
- **Quiz**: 20 marks (students can score 0-20)
- **Assignment**: 30 marks (students can score 0-30)

### How it's used:
- When entering results, you'll enter the student's score (e.g., 75 out of 100)
- The system calculates the percentage: `(marks_obtained / total_marks) × 100`
- Example: Student gets 75/100 = 75% = Grade B

---

## 2. Weight (Contribution to Final Grade)

### What it is:
- The **percentage contribution** of this assessment to the student's final term grade
- This determines how much this assessment "counts" toward the overall term result

### Examples:
- **Final Exam**: 40% weight (contributes 40% to term grade)
- **Mid-Term Exam**: 30% weight (contributes 30% to term grade)
- **Quizzes**: 20% weight (contributes 20% to term grade)
- **Assignments**: 10% weight (contributes 10% to term grade)
- **Total**: 100% (all assessments in a term should add up to 100%)

### How it's used:
- When calculating the final term grade, each assessment's contribution is weighted
- Formula: `(student_score / total_marks) × weight = weighted_score`
- All weighted scores are summed to get the final term grade

---

## Real-World Example

### Scenario: Mathematics Subject - First Term

Let's say you have these assessments for Mathematics:

| Assessment | Total Marks | Weight | Student Score | Calculation |
|------------|-------------|--------|---------------|-------------|
| Quiz 1 | 20 | 10% | 18/20 | (18/20) × 10% = 9.0% |
| Quiz 2 | 20 | 10% | 16/20 | (16/20) × 10% = 8.0% |
| Assignment 1 | 30 | 15% | 25/30 | (25/30) × 15% = 12.5% |
| Mid-Term Exam | 50 | 25% | 40/50 | (40/50) × 25% = 20.0% |
| Final Exam | 100 | 40% | 75/100 | (75/100) × 40% = 30.0% |
| **TOTAL** | **220** | **100%** | **174/220** | **79.5% (Grade B)** |

### Breakdown:
1. **Total Marks** values (20, 20, 30, 50, 100) are independent - they just define the maximum for each assessment
2. **Weight** values (10%, 10%, 15%, 25%, 40%) add up to 100% - they define how much each assessment contributes
3. The student's **final term grade** is calculated using weighted averages: 79.5%

---

## Common Patterns

### Pattern 1: Standard Exam Structure
```
Final Exam:     100 marks, 40% weight
Mid-Term:       50 marks,  30% weight
Quizzes:        20 marks,  20% weight (each quiz)
Assignments:    30 marks,  10% weight
```

### Pattern 2: Continuous Assessment
```
Class Tests:    30 marks,  30% weight (multiple tests)
Projects:       50 marks,  20% weight
Quizzes:        20 marks,  20% weight
Final Exam:     100 marks, 30% weight
```

### Pattern 3: Exam-Heavy Structure
```
Final Exam:     100 marks, 50% weight
Mid-Term:       100 marks, 30% weight
Assignments:    50 marks,  20% weight
```

---

## Important Notes

### ✅ Do's:
- **Total Marks** can be any value (20, 30, 50, 100, etc.) - it's independent
- **Weight** should add up to **100%** across all assessments in a term for a subject
- Use consistent mark scales (e.g., all out of 100, or all out of 50) for easier understanding
- Higher weight = more important assessment (e.g., final exams usually have higher weight)

### ❌ Don'ts:
- Don't confuse Total Marks with Weight - they're different!
- Don't make weights add up to more than 100% (the system allows it, but it's incorrect)
- Don't use Total Marks to indicate importance - use Weight instead
- Don't make all assessments have the same Total Marks - vary them based on assessment type

---

## How the System Uses These Values

### 1. When Entering Results:
- You enter: Student scored **75** out of **100** (Total Marks)
- System calculates: 75% = Grade B

### 2. When Calculating Term Grade:
- System uses: (75/100) × **40%** (Weight) = 30% contribution
- Adds all weighted contributions together
- Final term grade = Sum of all weighted contributions

### 3. When Displaying Results:
- Shows: "75/100 (75%)" - using Total Marks
- Shows: "Weight: 40%" - using Weight
- Shows: "Contribution: 30%" - calculated from both

---

## Quick Reference

| Field | Purpose | Example | Range |
|-------|---------|---------|-------|
| **Total Marks** | Maximum score for this assessment | 100, 50, 30 | 1 - 999.99 |
| **Weight** | Percentage contribution to term grade | 40%, 30%, 20% | 0 - 100% |

**Remember**: 
- **Total Marks** = "How many marks is this assessment worth?"
- **Weight** = "How much does this assessment count toward the final grade?"

---

## Example Workflow

1. **Create Final Exam**:
   - Total Marks: `100` (exam is out of 100)
   - Weight: `40` (contributes 40% to term grade)

2. **Create Quiz**:
   - Total Marks: `20` (quiz is out of 20)
   - Weight: `10` (contributes 10% to term grade)

3. **Enter Results**:
   - Student gets 85/100 on Final Exam → 85% → contributes (85% × 40%) = 34% to term grade
   - Student gets 18/20 on Quiz → 90% → contributes (90% × 10%) = 9% to term grade

4. **Final Term Grade**:
   - Sum of all weighted contributions = Final term percentage
   - Example: 34% + 9% + ... = 82% (Grade A)

---

**Need Help?** If you're unsure about what values to use, consult your school's academic policy or grading scheme.

