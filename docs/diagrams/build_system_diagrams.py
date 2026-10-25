"""Build editable diagrams.net pages and Mermaid sources from SYSTEM_DIAGRAMS.md.

Standard-library only. This reads documentation, not application data.
"""
from pathlib import Path
import re
import xml.etree.ElementTree as ET
from collections import deque, defaultdict

HERE = Path(__file__).resolve().parent
SOURCE = HERE.parent / "SYSTEM_DIAGRAMS.md"
text = SOURCE.read_text(encoding="utf-8")
blocks = re.findall(r"```mermaid\n(.*?)\n```", text, re.S)
titles = []
for match in re.finditer(r"```mermaid\n", text):
    titles.append(re.findall(r"^## (.+)$", text[:match.start()], re.M)[-1])

document = ET.Element("mxfile", host="app.diagrams.net", type="device")
all_nodes = set()
edge_count = 0
for page, (title, block) in enumerate(zip(titles, blocks), 1):
    direction = block.splitlines()[0].split()[1]
    nodes, edges = {}, []
    for line in block.splitlines()[1:]:
        node = re.fullmatch(r'\s*(\w+)(\[\("|\["|\{")(.*?)("\)\]|"\]|"\})\s*', line)
        edge = re.fullmatch(r"\s*(\w+)\s*(-->|-\.->)(?:\|([^|]*)\|)?\s*(\w+)\s*", line)
        if node:
            nodes[node[1]] = (node[3], node[2])
        elif edge:
            edges.append((edge[1], edge[4], edge[3] or "", edge[2] == "-.->"))
        elif line.strip():
            raise ValueError(f"Unsupported diagram line: {line}")
    assert nodes, title
    assert all(a in nodes and b in nodes for a, b, _, _ in edges), title
    # Breadth-first layers keep every node readable. Connections remain editable.
    incoming = {key: 0 for key in nodes}
    outgoing = defaultdict(list)
    for a, b, _, _ in edges:
        incoming[b] += 1
        outgoing[a].append(b)
    roots = [key for key in nodes if not incoming[key]] or [next(iter(nodes))]
    levels = {key: 0 for key in roots}
    queue = deque(roots)
    while queue:
        a = queue.popleft()
        for b in outgoing[a]:
            if b not in levels:
                levels[b] = levels[a] + 1
                queue.append(b)
    for key in nodes:
        levels.setdefault(key, 0)
    groups = defaultdict(list)
    for key in nodes:
        groups[levels[key]].append(key)
    widest = max(map(len, groups.values()))
    depth = max(groups) + 1
    width = max(1200, (depth if direction == "LR" else widest) * 330 + 100)
    height = max(850, (widest if direction == "LR" else depth) * 170 + 160)
    diagram = ET.SubElement(document, "diagram", id=f"svp-{page}", name=title)
    model = ET.SubElement(diagram, "mxGraphModel", dx="1200", dy="850", grid="1", gridSize="10", page="1", pageScale="1", pageWidth=str(width), pageHeight=str(height), math="0", shadow="0")
    root = ET.SubElement(model, "root")
    ET.SubElement(root, "mxCell", id="0")
    ET.SubElement(root, "mxCell", id="1", parent="0")
    heading = ET.SubElement(root, "mxCell", id="title", value=title, style="text;html=1;fontSize=24;fontStyle=1;align=left;", vertex="1", parent="1")
    ET.SubElement(heading, "mxGeometry", x="40", y="20", width=str(width - 80), height="40", **{"as": "geometry"})
    if 3 <= page <= 6:
        legend = ET.SubElement(root, "mxCell", id="legend", value="เส้นทึบ: Foreign Key จริง | เส้นประ: ความสัมพันธ์ในโค้ด", style="text;html=1;fontSize=15;align=left;", vertex="1", parent="1")
        ET.SubElement(legend, "mxGeometry", x="40", y="65", width="800", height="30", **{"as": "geometry"})
    for level, keys in groups.items():
        offset = (widest - len(keys)) / 2
        for index, key in enumerate(keys):
            label, shape = nodes[key]
            x = 40 + (level if direction == "LR" else index + offset) * 330
            y = 120 + (index + offset if direction == "LR" else level) * 170
            style = "rounded=1;whiteSpace=wrap;html=1;fillColor=#edf4fa;strokeColor=#64748b;fontColor=#16344a;fontSize=16;spacing=12;"
            if shape == '{"':
                style += "shape=rhombus;"
            elif shape == '[("':
                style += "shape=cylinder3;boundedLbl=1;size=15;"
            cell = ET.SubElement(root, "mxCell", id=key, value=label, style=style, vertex="1", parent="1")
            ET.SubElement(cell, "mxGeometry", x=str(x), y=str(y), width="280", height="105", **{"as": "geometry"})
            all_nodes.add(label.split("<br/>")[0])
    for index, (a, b, label, dashed) in enumerate(edges):
        style = "edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;endArrow=block;endFill=1;fontSize=13;labelBackgroundColor=#ffffff;strokeColor=#64748b;"
        if dashed:
            style += "dashed=1;"
        cell = ET.SubElement(root, "mxCell", id=f"edge-{index}", value=label, style=style, edge="1", parent="1", source=a, target=b)
        ET.SubElement(cell, "mxGeometry", relative="1", **{"as": "geometry"})
    edge_count += len(edges)
    (HERE / f"{page:02d}-system.mmd").write_text(block + "\n", encoding="utf-8")

target = HERE / "svp-intranet-system.drawio"
ET.indent(document)
ET.ElementTree(document).write(target, encoding="utf-8", xml_declaration=True)
assert len(ET.parse(target).getroot().findall("diagram")) == 9
expected = "announcement_reads announcements backup_checklist_history backup_checklists controlled_document_history controlled_documents department device device_type kpi_definitions kpi_form_deliveries kpi_form_history kpi_forms kpi_history kpi_results machine_events machine_pm_records machine_pm_schedules machine_schema machines pm_inspection_form pm_record pm_schedule portal_notifications position users work_events work_items".split()
assert not set(expected) - all_nodes, set(expected) - all_nodes
print(f"Validated {len(blocks)} diagrams, {edge_count} edges, all {len(expected)} tables, and editable drawio XML.")
