# Analyse de la table `test_session` – Test effectué / Étape actuelle

Ce document explique comment déterminer, à partir de la table `test_session` et de ses colonnes en base, **si le test d’orientation est effectué** et **à quelle étape** se trouve l’utilisateur lorsqu’il est en cours.

---

## 1. Source de vérité : test effectué

- **Colonne à utiliser** : `is_completed` (booléen).
- **Règle** :
  - `is_completed = true` → **test effectué** (toutes les étapes sont terminées, rapport disponible).
  - `is_completed = false` → test **non terminé** (en cours ou abandonné).
- **Complément** : quand le test est terminé, `completed_at` est renseigné (date/heure de fin).

En résumé : **ne pas se baser uniquement sur le JSON** pour savoir si le test est terminé ; la source de vérité est **`is_completed`** (et éventuellement `completed_at`).

---

## 2. Structure des colonnes JSON

La table `test_session` contient notamment :

| Colonne        | Type | Rôle |
|----------------|------|------|
| `metadata`     | JSON | Infos générales : langue, dates, durées par étape, version. |
| `current_step` | JSON | État complet du test : étape actuelle, liste des étapes, étapes complétées, données de chaque étape (personalInfo, riasec, personality, etc.). |

Pour l’**étape actuelle** et la **progression**, il faut lire **`current_step`** (et éventuellement `metadata` pour les durées).

---

## 3. Exemple : session à peine démarrée (`metadata` + `current_step` minimal)

**metadata** (exemple) :

```json
{
  "selectedLanguage": "fr",
  "startedAt": "2026-01-21T13:15:36+00:00",
  "stepDurations": {"personalInfo": 0},
  "version": "1.0",
  "lastUpdated": "2026-01-21T13:15:36+00:00"
}
```

- Pas de `currentStep` ni `completedSteps` dans **metadata** ; la progression détaillée est dans **`current_step`**.
- Si **`current_step`** ne contient qu’un début de structure (ex. seulement `personalInfo` dans `stepDurations` côté metadata, ou peu de clés dans current_step) → l’utilisateur est au tout début (souvent **personalInfo** ou **welcome**).

**Conclusion** :  
- Test effectué ? → **Non** (`is_completed = false`).  
- Étape actuelle ? → Lire la clé **`currentStep`** dans le JSON **`current_step`** (voir ci‑dessous).

---

## 4. Exemple : session complète (test terminé ou presque)

**current_step** (exemple de structure lorsque le test est avancé ou terminé) :

```json
{
  "currentStep": "languages",
  "steps": ["welcome", "personalInfo", "riasec", "personality", "aptitude", "interests", "career", "constraints", "languages"],
  "completedSteps": ["personalInfo", "riasec", "personality", "aptitude", "interests", "career", "constraints", "languages"],
  "personalInfo": { ... },
  "riasec": { ... },
  "personality": { ... },
  "aptitude": { ... },
  "interests": { ... },
  "career": { ... },
  "constraints": { ... },
  "languages": { ... }
}
```

- **`currentStep`** : étape courante (ex. `"languages"` = dernière étape).
- **`completedSteps`** : tableau des étapes déjà validées.
- Si **`completedSteps`** contient toutes les étapes métier (jusqu’à `languages`) et que le front envoie la fin de test → le backend met **`is_completed = true`** et **`completed_at`** est renseigné.

**Conclusion** :  
- Test effectué ? → **Oui** si `is_completed = true` (et `completed_at` présent).  
- Étape actuelle (si non complété) ? → **`current_step` → clé `currentStep`** (ex. `"languages"`).

---

## 5. Comment déduire l’étape actuelle (si test non effectué)

1. Lire le JSON de la colonne **`current_step`**.
2. Récupérer la clé **`currentStep`** (string). C’est l’étape en cours.
3. Si `currentStep` est absent, on peut déduire :
   - à partir de **`completedSteps`** : la prochaine étape après la dernière valeur de `completedSteps` (ordre ci‑dessous),
   - ou considérer par défaut la première étape (ex. `personalInfo`).

**Ordre des étapes** (test d’orientation) :

| Étape (valeur `currentStep`) | Libellé court        |
|-----------------------------|----------------------|
| `welcome`                   | Accueil              |
| `personalInfo`              | Infos personnelles   |
| `riasec`                    | RIASEC               |
| `personality`               | Personnalité         |
| `aptitude`                  | Aptitudes            |
| `interests`                 | Intérêts             |
| `career`                    | Métiers              |
| `constraints`               | Contraintes          |
| `languages`                 | Langues              |

Test **effectué** = toutes ces étapes sont complétées et **`is_completed = true`**.

---

## 6. Résumé pratique

| Question                    | Où regarder                    | Règle |
|----------------------------|---------------------------------|-------|
| Le test est‑il effectué ? | Colonne **`is_completed`**      | `true` = oui, `false` = non (en cours ou abandonné). |
| Date de fin du test ?      | Colonne **`completed_at`**     | Renseignée quand `is_completed = true`. |
| À quelle étape est-il ?    | Colonne **`current_step`** (JSON) | Clé **`currentStep`** (ex. `"personalInfo"`, `"riasec"`, …, `"languages"`). |
| Détail de la progression  | Colonne **`current_step`** (JSON) | **`completedSteps`** = étapes déjà validées ; **`steps`** = liste des étapes. |
| Durées par étape           | Colonne **`metadata`** (JSON)  | Clé **`stepDurations`** (ex. `{"personalInfo": 0, "riasec": 120}`). |

En SQL (exemple) :

- Test effectué : `SELECT * FROM test_session WHERE test_type = 'orientation' AND is_completed = 1;`
- Étape actuelle (session en cours) : lire la colonne `current_step` (JSON) puis la clé `currentStep`.

L’API admin expose désormais **`orientationCurrentStep`** pour les utilisateurs ayant une session en cours (test non terminé), en se basant sur cette logique.
