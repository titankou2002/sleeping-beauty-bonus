#!/usr/bin/env python3
"""
Split SleeperService.php into trait files by feature group.
Restore: git checkout pre-split-v1 -- classes/SleeperService.php
         OR php restore_sleeper.php
"""
import re, os, shutil

SRC = 'classes/SleeperService.php'
OUT = 'classes'
TRAIT_DIR = os.path.join(OUT, 'traits')

# Method → Trait mapping (feature groups)
# 'base' = keep in main class
METHOD_MAP = {
    # === Data/Shared Access ===
    'getClient': 'Data',
    'getSalesRepAreaMap': 'Data',
    'getSeriesMap': 'Data',
    'getStockMap': 'Data',
    'getInventorySummary': 'Data',
    'getMetaMap': 'Data',
    'getProductProfileMap': 'Data',
    'getSleeperConfig': 'Data',
    'getSleeperCostMap': 'Data',
    'getPriceCostMap': 'Data',
    'getActiveDisplaysMap': 'Data',
    'getReservationMap': 'Data',
    'getSalesYearCacheRows': 'Data',
    'loadSalesStats': 'Data',
    'rebuildSalesYearCache': 'Data',
    'getReportHistory': 'Data',
    'recordReportSnapshot': 'Data',
    'recordInventorySnapshot': 'Data',
    'getProductHistory': 'Data',
    'recordProductHistory': 'Data',
    'getProductLifecycle': 'Data',

    # === Bonus/Commission ===
    'getSleeperSalesByMonth': 'Bonus',
    'syncTrialSheet': 'Bonus',
    'recalcTrialSheet': 'Bonus',
    'updateTrialRow': 'Bonus',
    'readTrialSheet': 'Bonus',
    'getBonusSummary': 'Bonus',
    'getYearSummary': 'Bonus',
    'ensureTrialSheet': 'Bonus',

    # === Customer Analysis ===
    'getCustomerAnalysis': 'Customer',
    'getCustomerDetail': 'Customer',
    'getCustomerTimeline': 'Customer',
    'getCustomerWarRoom': 'Customer',
    'getCustomerSalesBreakdown': 'Customer',

    # === Product Overview ===
    'getSleeperProductOverview': 'Product',
    'getNormalProductOverview': 'Product',
    'getDiscontinuedProductOverview': 'Product',
    'getNewProductAnalysis': 'Product',
    'getProductRestockAdvisor': 'Product',
    'callGeminiRestockAdvisor': 'Product',
    'computeStagnancyDiagnostics': 'Product',

    # === Reports ===
    'getStrategyReport': 'Report',
    'getStrategyPeriodMeta': 'Report',
    'getPreviousStrategyPeriodMeta': 'Report',
    'getYoyStrategyPeriodMeta': 'Report',
    'buildStrategySummaryFromBuckets': 'Report',
    'buildStrategyCompare': 'Report',
    'buildFieldActivityCompare': 'Report',
    'buildDeltaLeaders': 'Report',
    'buildFieldActivityReport': 'Report',
    'getContractMeetingSummary': 'Report',
    'getMeetingReport': 'Report',
    'getCompanyReportStats': 'Report',
    'getGroupMeetingReport': 'Report',
    'getCompanyCacheCustomers': 'Report',
    'getCompanyProductStats': 'Report',
    'getCompanyContractSummary': 'Report',
    'getCompanyTopProducts': 'Report',
    'getCompanyQuarterStats': 'Report',
    'getCompanySalesRepStats': 'Report',
    'getCompanyInventoryBreakdown': 'Report',
    'getGroupDetailedReport': 'Report',
    'getManagerReports': 'Report',
    'saveManagerReport': 'Report',

    # === Rep Analysis ===
    'getRepAnalysis': 'Rep',

    # === AI Advisor ===
    'getAiAdvisor': 'Ai',
    'buildAdvisorSummary': 'Ai',
    'callGeminiAdvisor': 'Ai',
    'customerAiChat': 'Ai',
    'newProductAiChat': 'Ai',
    'globalAiChat': 'Ai',

    # === Project Scan ===
    'scanProjectFlags': 'Project',
    'classifyTaskLabel': 'Project',
    'parseWorkLogCustomers': 'Project',
    'categorizeVisitTask': 'Project',

    # === Daily Mail ===
    'sendDailyPerformanceReport': 'Mail',

    # === base helpers (stay in main class) ===
    '__construct': 'base',
    'normalizeCustomerName': 'base',
    'displayCustomerName': 'base',
    'normalizeAddress': 'base',
    'normalizeSizeLabel': 'base',
    'normalizeContractHealthLabel': 'base',
    'isLogisticsAddress': 'base',
    'shortProjectName': 'base',
    'peakShavedAverage': 'base',
    'matchesStrategyPeriod': 'base',
    'truncateReport': 'base',
    'safeRatio': 'base',
    'calcPearson': 'base',
    'calcMultiplier': 'base',
    'safeAvg': 'base',
    'cleanSku': 'base',
    'normalizeSalesRep': 'base',
    'extractProjectFromNote': 'base',
    'optFloat': 'base',
    'parseDate': 'base',
    'formatRocDate': 'base',
    'findHeader': 'base',
    'getVal': 'base',
    'buildProductCustomerRows': 'base',
    'padToColumn': 'base',
    'isSampleRow': 'base',
    'extractIdFromUrl': 'base',
    'fmtW': 'base',
}

def extract_methods(content):
    """Parse PHP file and extract method definitions with line ranges."""
    lines = content.split('\n')
    methods = []
    i = 0
    while i < len(lines):
        line = lines[i]
        # Match method declarations: visibility [static] function name(...)
        m = re.match(r'^\s*(public|private|protected)\s+(static\s+)?function\s+(\w+)\s*\(', line)
        if m:
            name = m.group(3)
            # Find the opening brace of the method body
            # (not the class opening brace, not property defaults)
            start = i
            # Find the opening { of this method
            brace_pos = None
            for j in range(i, min(i + 5, len(lines))):
                idx = lines[j].find('{')
                if idx >= 0:
                    # Make sure { is not in a string
                    brace_pos = (j, idx)
                    break
            if brace_pos is None:
                i += 1
                continue
            brace_line, brace_col = brace_pos
            # Count braces to find matching close
            depth = 0
            end = brace_line
            for j in range(brace_line, len(lines)):
                for ch in lines[j]:
                    if ch == '{':
                        depth += 1
                    elif ch == '}':
                        depth -= 1
                if depth == 0:
                    end = j
                    break
            methods.append({
                'name': name,
                'start': start,
                'end': end,
                'lines': lines[start:end+1]
            })
            i = end + 1
        else:
            i += 1
    return methods

def main():
    with open(SRC, 'r', encoding='utf-8') as f:
        content = f.read()

    # Separate class header (<?php + class declaration + properties + constructor start)
    # from the rest
    lines = content.split('\n')

    # Find the class declaration line
    class_line = None
    for i, line in enumerate(lines):
        if re.match(r'^class\s+SleeperService', line):
            class_line = i
            break

    # Header: everything up to and including the opening { of the class
    header_lines = lines[:class_line+2]  # class declaration + opening brace
    # Footer: the closing } of the class (last line)
    footer_lines = [lines[-1]]

    # Middle: method definitions + property declarations
    body_lines = lines[class_line+2:-1]

    # Reconstruct body text for parsing
    body_text = '\n'.join(body_lines)
    methods = extract_methods(body_text)

    # Group methods by trait
    trait_methods = {}
    base_lines = []
    assigned = set()

    # Handle properties (lines that are not methods)
    remaining = list(body_lines)

    # First pass: identify all line ranges that belong to methods
    method_ranges = set()
    for m in methods:
        name = m['name']
        target = METHOD_MAP.get(name, 'base')
        if target == 'base':
            for ln in range(m['start'], m['end'] + 1):
                method_ranges.add(ln)
                base_lines.append((ln, body_lines[ln]))
            assigned.add(name)
        else:
            for ln in range(m['start'], m['end'] + 1):
                method_ranges.add(ln)
            trait_methods.setdefault(target, []).append(m)
            assigned.add(name)

    # Check for unassigned methods
    for m in methods:
        if m['name'] not in assigned:
            print(f"WARNING: Unassigned method: {m['name']} at line ~{m['start']}")
            # Default to base
            for ln in range(m['start'], m['end'] + 1):
                method_ranges.add(ln)
                base_lines.append((ln, body_lines[ln]))

    # Collect non-method lines (properties, static vars, comments between methods)
    for ln in range(len(body_lines)):
        if ln not in method_ranges:
            base_lines.append((ln, body_lines[ln]))

    base_lines.sort(key=lambda x: x[0])

    # Write base SleeperService.php
    full_header = lines[:class_line+2]
    props = []
    for ln, line in base_lines:
        props.append(line)
    trait_names = sorted(set(METHOD_MAP.values()) - {'base'})
    trait_uses = 'use ' + ',\n    '.join(f'{t}Trait' for t in trait_names) + ';'

    # Rebuild base source maintaining original structure
    base_source = '\n'.join(full_header)
    base_source += '\n'
    base_source += '\n'.join(props)
    base_source += '\n' + '}\n'

    # Actually, we need to be more careful. Let's rewrite SleeperService.php properly.
    # New SleeperService.php: header + properties + trait use + remaining methods + footer
    
    # Extract property declarations and static variables
    property_lines = []
    method_start_lines = set(m['start'] for m in methods)
    
    for ln in range(len(body_lines)):
        if ln not in method_ranges:
            property_lines.append(body_lines[ln])
        elif ln in method_ranges and ln in method_start_lines:
            m_name = None
            for m in methods:
                if m['start'] == ln:
                    m_name = m['name']
                    break
            if METHOD_MAP.get(m_name, 'base') == 'base':
                property_lines.append(body_lines[ln])
                # Add all lines of this method
                for m in methods:
                    if m['start'] == ln:
                        for ml in range(m['start']+1, m['end']+1):
                            property_lines.append(body_lines[ml])
    
    new_sleeper = '<?php\n'
    new_sleeper += '// ====== SleeperService ======\n'
    new_sleeper += 'class SleeperService\n'
    new_sleeper += '{\n'
    new_sleeper += f'    {trait_uses}\n\n'
    new_sleeper += '\n'.join(property_lines)
    new_sleeper += '\n}\n'

    # Write trait files
    os.makedirs(TRAIT_DIR, exist_ok=True)
    
    for tname in trait_names:
        tmethods = trait_methods.get(tname, [])
        tcode = f"<?php\n// ====== {tname}Trait ======\ntrait {tname}Trait\n{{\n"
        for m in tmethods:
            tcode += '\n' + '\n'.join(m['lines']) + '\n'
        tcode += '}\n'
        
        tpath = os.path.join(TRAIT_DIR, f'{tname}Trait.php')
        with open(tpath, 'w', encoding='utf-8') as f:
            f.write(tcode)
        print(f"  Wrote {tpath} ({len(tmethods)} methods)")

    # Write new SleeperService.php
    with open(SRC, 'w', encoding='utf-8') as f:
        f.write(new_sleeper)
    print(f"\nWrote {SRC}")
    print(f"  Traits: {', '.join(trait_names)}")
    print(f"  Methods kept in base: {len([m for m in methods if METHOD_MAP.get(m['name'], 'base') == 'base'])}")
    print(f"  Methods moved to traits: {len(methods) - len([m for m in methods if METHOD_MAP.get(m['name'], 'base') == 'base'])}")

if __name__ == '__main__':
    main()
