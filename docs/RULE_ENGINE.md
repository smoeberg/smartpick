# SmartPick V3 - Rule Engine Specification

## Nuværende tilstand (as-is)

`SmartPickRuleEngine.class.php` findes og virker, men er **hårdkodet PHP**, ikke data-drevet:

```php
class SmartPickRuleEngine {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function evaluateOrderRules($orderData) {
        $carrier = 'Shipmondo Standard';
        $priority = 'Normal';
        if (($orderData['country_code'] ?? '') == 'NO') $carrier = 'PostNord Direct NO';
        if (($orderData['weight_kg'] ?? 0) > 20) $carrier = 'GLS Heavy Freight';
        if (($orderData['is_vip'] ?? false)) $priority = 'VIP High Priority';
        return ['assigned_carrier' => $carrier, 'priority' => $priority];
    }
}
```

Dette virker fint til de fire regler der findes i dag, men hver ny regel kræver en kodeændring og en deployment. Det er præcis det problem V3-arkitekturen skal løse.

## Målarkitektur (to-be)

Regler flyttes til data (YAML eller databasetabel), og en generisk evaluator fortolker dem runtime.

### Regelskema

```yaml
rule_id: R-001
name: "Norge -> PostNord Direct"
domain: shipping
priority: 10          # lavere tal = evalueres først
active: true
when:
  all:
    - field: country_code
      op: equals
      value: "NO"
then:
  set:
    assigned_carrier: "PostNord Direct NO"
```

```yaml
rule_id: R-002
name: "Tung pakke -> GLS Heavy Freight"
domain: shipping
priority: 20
active: true
when:
  all:
    - field: weight_kg
      op: greater_than
      value: 20
then:
  set:
    assigned_carrier: "GLS Heavy Freight"
```

```yaml
rule_id: R-003
name: "VIP-kunde -> Høj prioritet"
domain: shipping
priority: 5
active: true
when:
  all:
    - field: is_vip
      op: equals
      value: true
then:
  set:
    priority: "VIP High Priority"
```

### Understøttede operatorer (v1 scope)

| Operator | Betydning |
|---|---|
| `equals` | Eksakt match |
| `not_equals` | Negation |
| `greater_than` / `less_than` | Numerisk sammenligning |
| `in` | Værdi findes i liste |
| `contains` | Substring/array-indhold |

`all` = AND-logik. `any` (OR) tilføjes når første use case kræver det — ikke før, for at undgå overengineering.

### Konfliktløsning

Regler evalueres i `priority`-rækkefølge (lavest først). Hvis to regler sætter samme felt, vinder den med lavest `priority`-tal, men **alle** matchende regler logges, så man kan se hvorfor et bestemt resultat blev valgt (`RuleEvaluationTrace`).

### Eksempel: Slotting-regel (nyt domæne, ikke kun shipping)

```yaml
rule_id: R-010
name: "Frostvarer -> Kold zone"
domain: storage
priority: 1
active: true
when:
  all:
    - field: product_class
      op: equals
      value: "frozen"
then:
  set:
    zone: "Cold"
```

Dette viser at regelmotoren skal være **domæneagnostisk** — samme evaluator, forskellige regelsæt pr. bounded context (`shipping`, `storage`, `picking`, osv.), ikke én motor kun for fragt.

## Migrationsplan fra as-is til to-be

1. **Trin 1 (ikke-brydende):** Byg den generiske YAML-evaluator som en *ny* klasse (`RuleEngine` i `application/services`), og lad `SmartPickRuleEngine::evaluateOrderRules()` kalde ind i den med de 3 eksisterende regler oversat til YAML. Gammel funktionssignatur bevares, så intet andet i kodebasen skal ændres.
2. **Trin 2:** Flyt reglerne til `rules/shipping.yaml` og lad admin-UI (`admin/smartpick_setup.php`) redigere dem uden kode-deployment.
3. **Trin 3:** Udvid til flere domæner (storage, picking) efterhånden som konkrete regel-behov opstår — undgå at bygge regler for domæner der endnu ikke har regelbehov.

## Ikke-mål (bevidst udeladt fra v1)

- Ingen visuel regel-builder (drag/drop) — YAML/tabel er nok til at starte
- Ingen indbygget AI-regelforslag i selve motoren (det hører til AI-domænet, ikke Rule Engine)
- Ingen understøttelse af tidsbaserede regler ("kun i weekender") før et konkret behov opstår
