# Plan de réussite – Étapes (UserProfile.planReussiteSteps)

Ce document décrit la structure et la logique des **étapes du plan de réussite** stockées dans `user_profile.plan_reussite_steps` (JSON).

---

## 1. Colonne en base

- **Table** : `user_profile`
- **Colonne** : `plan_reussite_steps` (type JSON, nullable)
- **Entité** : `UserProfile::planReussiteSteps` (array)

---

## 2. Structure du JSON

Exemple de valeur en base :

```json
{
  "reportStepCompleted": true,
  "reportStepCompletedAt": "2026-01-20T22:56:10",
  "step3_visited": true,
  "step4_visited": true,
  "step5_visited": true
}
```

| Clé | Type | Signification |
|-----|------|----------------|
| **reportStepCompleted** | boolean | L'utilisateur a consulté le rapport d'orientation (étape « Rapport »). Prérequis : test d'orientation complété. |
| **reportStepCompletedAt** | string (ISO datetime) | Date/heure à laquelle le rapport a été marqué comme consulté. Renseignée lorsque `reportStepCompleted` passe à `true`. |
| **step3_visited** | boolean | L'utilisateur a visité la page « Secteurs de métiers ». Prérequis : `reportStepCompleted === true`. |
| **step4_visited** | boolean | L'utilisateur a visité la page « Établissements ». Prérequis : `step3_visited === true`. |
| **step5_visited** | boolean | L'utilisateur a visité la page « Services ». Prérequis : `step4_visited === true`. |

---

## 3. Ordre des étapes et prérequis

L’ordre de déblocage est le suivant (voir `AuthController::updatePlanReussiteSteps`) :

1. **reportStepCompleted**  
   - Prérequis : test d’orientation complété (`TestSession` type `orientation`, `is_completed = true`).  
   - Quand c’est mis à `true`, le backend enregistre aussi **reportStepCompletedAt**.

2. **step3_visited** (Secteurs de métiers)  
   - Prérequis : `reportStepCompleted === true`.

3. **step4_visited** (Établissements)  
   - Prérequis : `step3_visited === true`.

4. **step5_visited** (Services)  
   - Prérequis : `step4_visited === true`.

---

## 4. API backend

- **Mise à jour** : `POST` ou `PUT` `/api/user/plan-reussite/steps` avec body `{ "step": "reportStepCompleted" }` (ou `step3_visited`, `step4_visited`, `step5_visited`).  
  Le contrôleur vérifie les prérequis avant de mettre l’étape à `true`.

- **Lecture** : `GET /api/user/plan-reussite/steps` renvoie `{ "planReussiteSteps": { ... } }`.

- **Admin** : la liste et le détail utilisateur (`/api/admin/users`, `/api/admin/users/{id}`) exposent `profile.planReussiteSteps` lorsqu’un profil existe.

---

## 5. Comment exploiter les données

- **Dernière étape atteinte** : parcourir dans l’ordre `reportStepCompleted` → `step3_visited` → `step4_visited` → `step5_visited` et prendre la dernière clé à `true`.
- **Progression globale** : compter le nombre de ces clés à `true` (max 4).
- **Date de consultation du rapport** : utiliser **reportStepCompletedAt** si présente.

L’interface admin (liste des utilisateurs) peut afficher par exemple « 4/4 » ou « Rapport ✓ · Secteurs ✓ · Établ. ✓ · Services ✓ » à partir de ce JSON.
