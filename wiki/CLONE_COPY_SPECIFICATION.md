# Clone and Copy Behavior Specification

## SelfTables Framework - Variable and Object Cloning/Copying Behavior

**Version:** 1.0  
**Date:** September 24, 2025  
**Framework:** SelfTables 0.0.5.9-SNAPSHOT

---

## Table of Contents

1. [Overview](#overview)
2. [Class Hierarchy](#class-hierarchy)
3. [STBaseTable Clone Behavior](#stbasetable-clone-behavior)
4. [STDbTable Clone Behavior](#stdbtable-clone-behavior)
5. [STDbSelector Clone Behavior](#stdbselector-clone-behavior)
6. [STBaseContainer/STObjectContainer Behavior](#stbasecontainer-stobjectcontainer-behavior)
7. [Variable Categories](#variable-categories)
8. [PHP Clone Mechanics](#php-clone-mechanics)
9. [Best Practices](#best-practices)

---

## Overview

This specification documents the complete cloning and copying behavior for all variables, objects, and arrays across the core SelfTables framework classes. The framework implements custom `__clone()` methods that override PHP's default shallow copy behavior.

---

## Class Hierarchy

```
STBaseTable
├── STDbTable
│   └── STDbSelector
└── [Other Table Classes]

STBaseContainer
└── STObjectContainer
    └── STDatabase
```

---

## STBaseTable Clone Behavior

### Method: `__clone()`

**Location:** `environment/base/STBaseTable.php:328`

### Variables Reset to Default Values:
| Variable | Type | Default Value | Reason |
|----------|------|---------------|---------|
| `$ID` | int | Incremented global counter | Unique instance identification |
| `$bInsert` | boolean | `true` | Reset modification permissions |
| `$bUpdate` | boolean | `true` | Reset modification permissions |
| `$bDelete` | boolean | `true` | Reset modification permissions |
| `$doTableSorting` | boolean | `true` | Reset UI behavior |
| `$bShowName` | boolean | `true` | Reset display behavior |
| `$bLimitOwn` | boolean | `true` | Reset query limitations |
| `$bModifyFk` | boolean | `true` | Reset FK modification rights |
| `$bModifiedByQuery` | boolean | `false` | Reset query modification flag |
| `$aSetAlso` | array | `array()` | **CLEARED - Data loss occurs** |
| `$aCallbacks` | array | `array()` | **CLEARED - Callback loss occurs** |
| `$nFirstRowSelect` | int | `0` | Reset pagination |
| `$nMaxRowSelect` | mixed | `null` | Reset row limit |
| `$nAktSelectedRow` | int | `0` | Reset current row |
| `$bAlwaysIndex` | boolean | `true` | Reset indexing behavior |
| `$listArrangement` | constant | `STHORIZONTAL` | Reset layout |
| `$oSearchBox` | object | `null` | Clear search box reference |

### Variables Preserved (PHP Default Shallow Copy):
| Variable | Type | Behavior | Notes |
|----------|------|----------|-------|
| `$Name` | string | **COPIED** | Table name preserved |
| `$title` | string | **COPIED** | Table title preserved |
| `$sPKColumn` | string | **COPIED** | Primary key column preserved |
| `$columns` | array | **COPIED** | Column definitions preserved |
| `$aAliases` | array | **COPIED** | Column aliases preserved |
| `$titles` | array | **COPIED** | Column titles preserved |
| `$identification` | array | **COPIED** | Identification columns preserved |
| `$show` | array | **COPIED** | Display columns preserved |
| `$asOrder` | array | **COPIED** | Ordering configuration preserved |
| `$aLinkAddresses` | array | **COPIED** | Link configurations preserved |
| All other arrays | array | **COPIED** | Most arrays are preserved |

### Global State Changes:
- `$__static_global_STBaseTable_ID[$this->Name]++` - Increments global ID counter

---

## STDbTable Clone Behavior

### Method: `__clone()`

**Location:** `environment/db/STDbTable.php:119`

### Inheritance:
1. **First calls:** `STBaseTable::__clone()` (inherits all reset behavior above)
2. **Then applies:** STDbTable-specific cloning logic

### Variables Set as References (Shared Data):
| Variable | Type | Behavior | Source |
|----------|------|----------|--------|
| `$FK` | array | **REFERENCED** | `&$main->FK` |
| `$aFks` | array | **REFERENCED** | `&$main->aFks` |
| `$aBackJoin` | array | **REFERENCED** | `&$main->aBackJoin` |

### Variables Preserved from STBaseTable:
- All variables marked as "COPIED" in STBaseTable section
- **Exception:** `$aSetAlso` is still **CLEARED** due to parent `__clone()`

### Database Connection Handling:
- `$db` object reference is preserved (shallow copy)
- `$container` object reference is preserved (shallow copy)

### Key Behavior:
- Foreign key relationships are **shared** across all cloned instances
- Changes to FK arrays affect **all clones** of the same table
- Main table is retrieved via `$this->db->getTable($this->Name)`

---

## STDbSelector Clone Behavior

### Method: `__clone()`

**Location:** `environment/db/STDbSelector.php:135`

### Inheritance:
1. **First calls:** `STDbTable::__clone()` (inherits all behavior above)
2. **Debug output only** - no additional logic

### Additional Variables (STDbSelector-specific):
| Variable | Type | Behavior | Notes |
|----------|------|----------|-------|
| `$selector` | array | **COPIED** | Selection criteria preserved |
| `$aoToTables` | array | **COPIED** | Related tables preserved |
| `$aNewSelects` | array | **COPIED** | New selections preserved |
| `$SqlResult` | mixed | **COPIED** | SQL result preserved |
| `$search` | array | **COPIED** | Search criteria preserved |

---

## STBaseContainer/STObjectContainer Behavior

### Clone Behavior:
- **No custom `__clone()` method** - uses PHP default shallow copy
- All properties are shallow copied
- Object references remain as references
- Arrays are copied

### Key Variables:
| Variable | Type | Behavior | Notes |
|----------|------|----------|-------|
| `$name` | string | **COPIED** | Container name preserved |
| `$db` | object | **REFERENCED** | Database connection shared |
| `$oGetTables` | array | **COPIED** | Table cache copied (but contains object references) |
| `$parentContainer` | object | **REFERENCED** | Parent reference preserved |

---

## Variable Categories

### 1. **Reset Variables** (Lose Data on Clone)
- `$aSetAlso` - **Data Loss Risk**
- `$aCallbacks` - **Data Loss Risk**
- All boolean flags (`$bInsert`, `$bUpdate`, `$bDelete`, etc.)
- Pagination variables (`$nFirstRowSelect`, `$nMaxRowSelect`, etc.)

### 2. **Referenced Variables** (Shared Across Clones)
- `$FK` - Foreign key definitions
- `$aFks` - Foreign key relationships
- `$aBackJoin` - Back-join relationships
- Database connections (`$db`)

### 3. **Copied Variables** (Independent Copies)
- `$Name` - Table name
- `$columns` - Column definitions
- `$aAliases` - Column aliases
- `$show` - Display configuration
- Most configuration arrays

### 4. **ID Variables** (Globally Unique)
- `$ID` - Auto-incremented per table name
- Uses global counter `$__static_global_STBaseTable_ID`

---

## PHP Clone Mechanics

### Default PHP Behavior:
1. **Shallow Copy:** All properties are copied
2. **Object References:** Remain as references (not deep copied)
3. **Arrays:** Are copied (new array, same content)
4. **Scalars:** Are copied (strings, integers, booleans)

### Framework Override:
1. **Custom __clone():** Modifies default behavior
2. **Selective Reset:** Some variables are intentionally cleared
3. **Reference Creation:** Some arrays become shared references
4. **Global State:** ID counters are updated

---

## Best Practices

### For Developers Using SelfTables:

#### 1. **Data Loss Awareness**
```php
// BEFORE cloning - save critical data
$originalSetAlso = $table->aSetAlso;
$clonedTable = clone $table;
// AFTER cloning - restore if needed
$clonedTable->aSetAlso = $originalSetAlso; // or use reference: &$originalSetAlso
```

#### 2. **Understanding Shared References**
```php
$table1 = $db->getTable('users');
$table2 = clone $table1;
// WARNING: FK changes affect BOTH tables
$table1->FK['new_relation'] = 'something';
// This change is visible in $table2->FK as well!
```

#### 3. **Safe Clone Pattern**
```php
function safeTableClone($originalTable) {
    // Save data that gets cleared
    $preserveData = [
        'aSetAlso' => $originalTable->aSetAlso,
        'aCallbacks' => $originalTable->aCallbacks
    ];
    
    // Clone the table
    $newTable = clone $originalTable;
    
    // Restore critical data (choose copy or reference as needed)
    $newTable->aSetAlso = $preserveData['aSetAlso']; // Copy
    // OR
    $newTable->aSetAlso = &$originalTable->aSetAlso; // Reference
    
    return $newTable;
}
```

#### 4. **Container Cloning**
```php
// Containers don't have custom clone logic
$container1 = new STObjectContainer('test', $db);
$container2 = clone $container1;
// All properties are shallow copied - object references remain shared
```

### For Framework Developers:

#### 1. **Modifying Clone Behavior**
When adding new variables that should be preserved or reset, update the appropriate `__clone()` method:

```php
// In STDbTable::__clone() for preserved arrays
$main = $this->db->getTable($this->Name);
$this->yourNewArray = &$main->yourNewArray; // Reference
// OR
$this->yourNewArray = $main->yourNewArray;   // Copy
```

#### 2. **Debugging Clone Issues**
- Use `STCheck::echoDebug("table", ...)` for clone tracking
- Monitor global ID counters
- Check reference vs copy behavior with `debug_zval_dump()`

---

## Conclusion

The SelfTables framework implements a sophisticated cloning mechanism that:

1. **Preserves** essential table structure and configuration
2. **Resets** operational state to clean defaults
3. **Shares** foreign key relationships across clones
4. **Clears** certain data arrays (potential data loss)
5. **Maintains** unique instance identification

Understanding this behavior is crucial for proper framework usage and avoiding unexpected data loss or reference sharing issues.

---

**Warning:** The `$aSetAlso` and `$aCallbacks` arrays are **cleared during cloning**. If you need to preserve this data, you must explicitly handle it before and after the clone operation.
