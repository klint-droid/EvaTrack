# -*- coding: utf-8 -*-
"""
Script to generate the complete EVATRACK Software Requirements Specification (SRS) v1.0
following the official University of San Jose-Recoletos (USJ-R) Capstone guidelines,
strictly aligned with the actual EvaTrack backend (Laravel) and frontend (React) system.
"""

import os
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls


def set_cell_background(cell, hex_color):
    """Sets background shading of a table cell."""
    shading_xml = f'<w:shd {nsdecls("w")} w:fill="{hex_color}"/>'
    cell._tc.get_or_add_tcPr().append(parse_xml(shading_xml))


def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    """Sets internal padding/margins for a table cell in dxa (1 pt = 20 dxa)."""
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(
        f'<w:tcMar {nsdecls("w")}>'
        f'<w:top w:w="{top}" w:type="dxa"/>'
        f'<w:bottom w:w="{bottom}" w:type="dxa"/>'
        f'<w:left w:w="{left}" w:type="dxa"/>'
        f'<w:right w:w="{right}" w:type="dxa"/>'
        f'</w:tcMar>'
    )
    tcPr.append(tcMar)


def set_table_borders(table, color="D1D5DB", sz="4"):
    """Applies clean, subtle borders to an entire table."""
    tblPr = table._tbl.tblPr
    borders_xml = parse_xml(
        f'<w:tblBorders {nsdecls("w")}>'
        f'<w:top w:val="single" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:bottom w:val="single" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:left w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'<w:insideH w:val="single" w:sz="{sz}" w:space="0" w:color="{color}"/>'
        f'<w:insideV w:val="none"/>'
        f'</w:tblBorders>'
    )
    tc_borders = tblPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tblBorders')
    if tc_borders is not None:
        tblPr.remove(tc_borders)
    tblPr.append(borders_xml)


def style_table(table, col_widths=None, header_bg="1E3A8A", alt_row=True):
    """Applies styling, headers, widths, and zebra-striping to tables."""
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(table)

    # Style Header Row
    for idx, cell in enumerate(table.rows[0].cells):
        set_cell_background(cell, header_bg)
        set_cell_margins(cell, top=120, bottom=120, left=150, right=150)
        cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
        for p in cell.paragraphs:
            p.paragraph_format.space_before = Pt(2)
            p.paragraph_format.space_after = Pt(2)
            for r in p.runs:
                r.font.bold = True
                r.font.size = Pt(9.5)
                r.font.color.rgb = RGBColor(255, 255, 255)
                r.font.name = "Calibri"

    # Style Data Rows
    for row_idx, row in enumerate(table.rows[1:], start=1):
        bg = "F8FAFC" if (alt_row and row_idx % 2 == 1) else "FFFFFF"
        for col_idx, cell in enumerate(row.cells):
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=90, bottom=90, left=140, right=140)
            cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
            for p in cell.paragraphs:
                p.paragraph_format.space_before = Pt(2)
                p.paragraph_format.space_after = Pt(2)
                p.paragraph_format.line_spacing = 1.15
                for r in p.runs:
                    r.font.size = Pt(9.0)
                    r.font.name = "Calibri"
                    r.font.color.rgb = RGBColor(30, 41, 59)

    # Set Column Widths if provided
    if col_widths:
        for row in table.rows:
            for idx, width in enumerate(col_widths):
                if idx < len(row.cells):
                    row.cells[idx].width = width


def add_styled_heading(doc, text, level):
    """Adds standard formatted headings with consistent color and spacing."""
    p = doc.add_paragraph()
    run = p.add_run(text)
    run.font.name = "Calibri"
    run.font.bold = True

    if level == 1:
        p.paragraph_format.space_before = Pt(18)
        p.paragraph_format.space_after = Pt(8)
        p.paragraph_format.keep_with_next = True
        run.font.size = Pt(15)
        run.font.color.rgb = RGBColor(30, 58, 138)  # Navy Blue
    elif level == 2:
        p.paragraph_format.space_before = Pt(13)
        p.paragraph_format.space_after = Pt(5)
        p.paragraph_format.keep_with_next = True
        run.font.size = Pt(12.5)
        run.font.color.rgb = RGBColor(15, 23, 42)  # Slate 900
    elif level == 3:
        p.paragraph_format.space_before = Pt(10)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.keep_with_next = True
        run.font.size = Pt(11)
        run.font.color.rgb = RGBColor(51, 65, 85)  # Slate 700
    return p


def add_body_p(doc, text, bold_prefix=None, space_after=6):
    """Adds a standard body paragraph with 1.15 line spacing."""
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(space_after)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = "Calibri"
        r_pre.font.size = Pt(10.5)
        r_pre.font.bold = True
        r_pre.font.color.rgb = RGBColor(15, 23, 42)
    r = p.add_run(text)
    r.font.name = "Calibri"
    r.font.size = Pt(10.5)
    r.font.color.rgb = RGBColor(51, 65, 85)
    return p


def add_bullet(doc, text, bold_prefix=None):
    """Adds a formatted bullet item."""
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = "Calibri"
        r_pre.font.size = Pt(10.5)
        r_pre.font.bold = True
        r_pre.font.color.rgb = RGBColor(15, 23, 42)
    r = p.add_run(text)
    r.font.name = "Calibri"
    r.font.size = Pt(10.5)
    r.font.color.rgb = RGBColor(51, 65, 85)
    return p


def build_srs_document(output_docx_path, output_md_path=None):
    doc = docx.Document()
    md_lines = []

    # Set 1-inch margins
    for sec in doc.sections:
        sec.top_margin = Inches(1.0)
        sec.bottom_margin = Inches(1.0)
        sec.left_margin = Inches(1.0)
        sec.right_margin = Inches(1.0)
        sec.page_width = Inches(8.5)
        sec.page_height = Inches(11.0)

    # =========================================================================
    # COVER PAGE
    # =========================================================================
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(30)
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run("UNIVERSITY OF SAN JOSE-RECOLETOS")
    r.font.name = "Calibri"
    r.font.size = Pt(14)
    r.font.bold = True
    r.font.color.rgb = RGBColor(30, 58, 138)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run("School of Computer Studies")
    r.font.name = "Calibri"
    r.font.size = Pt(12)
    r.font.color.rgb = RGBColor(71, 85, 105)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(45)
    r = p.add_run("Cebu City, Philippines")
    r.font.name = "Calibri"
    r.font.size = Pt(11)
    r.font.color.rgb = RGBColor(100, 116, 139)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(12)
    r = p.add_run("EVATRACK")
    r.font.name = "Calibri"
    r.font.size = Pt(24)
    r.font.bold = True
    r.font.color.rgb = RGBColor(30, 58, 138)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(16)
    r = p.add_run("Streamlined Evacuation Processes and Coordination Platform Integrating SafeTrack Demographics and ResQperation Response Support")
    r.font.name = "Calibri"
    r.font.size = Pt(13)
    r.font.bold = True
    r.font.color.rgb = RGBColor(15, 23, 42)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(4)
    r = p.add_run("Software Requirements Specification")
    r.font.name = "Calibri"
    r.font.size = Pt(14)
    r.font.color.rgb = RGBColor(71, 85, 105)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(40)
    r = p.add_run("Version 1.0")
    r.font.name = "Calibri"
    r.font.size = Pt(12)
    r.font.bold = True
    r.font.color.rgb = RGBColor(30, 58, 138)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(4)
    r = p.add_run("A Capstone Project Presented to the Faculty of the\nSchool of Computer Studies\nUniversity of San Jose-Recoletos")
    r.font.name = "Calibri"
    r.font.size = Pt(10.5)
    r.font.color.rgb = RGBColor(71, 85, 105)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(30)
    r = p.add_run("In Partial Fulfillment of the Requirements for\n")
    r.font.name = "Calibri"
    r.font.size = Pt(10.5)
    r.font.color.rgb = RGBColor(71, 85, 105)
    r_deg = p.add_run("Bachelor of Science in Information Technology / Associate in Computer Technology")
    r_deg.font.name = "Calibri"
    r_deg.font.size = Pt(10.5)
    r_deg.font.bold = True
    r_deg.font.color.rgb = RGBColor(15, 23, 42)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(4)
    r = p.add_run("Presented by:")
    r.font.name = "Calibri"
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = RGBColor(30, 58, 138)

    proponents = [
        "Klint M. Ruales",
        "Danica Gelbolingo",
        "Anna Rhea Villadolid",
        "Vhenz Cernal"
    ]
    for prop in proponents:
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(2)
        r = p.add_run(prop)
        r.font.name = "Calibri"
        r.font.size = Pt(10.5)
        r.font.color.rgb = RGBColor(15, 23, 42)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(25)
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run("Capstone Adviser: ")
    r.font.name = "Calibri"
    r.font.size = Pt(10.5)
    r.font.bold = True
    r.font.color.rgb = RGBColor(30, 58, 138)
    r_name = p.add_run("Mr. Stefan A.D. Flores")
    r_name.font.name = "Calibri"
    r_name.font.size = Pt(10.5)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run("Project Coordinator: ")
    r.font.name = "Calibri"
    r.font.size = Pt(10)
    r.font.bold = True
    r.font.color.rgb = RGBColor(71, 85, 105)
    r_name = p.add_run("Mr. Roderick A. Bandalan")
    r_name.font.name = "Calibri"
    r_name.font.size = Pt(10)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(25)
    r = p.add_run("Panel Chair: ")
    r.font.name = "Calibri"
    r.font.size = Pt(10)
    r.font.bold = True
    r.font.color.rgb = RGBColor(71, 85, 105)
    r_name = p.add_run("Mr. Eric Magto  |  ")
    r_name.font.name = "Calibri"
    r_name.font.size = Pt(10)
    r_pan = p.add_run("Panelist: ")
    r_pan.font.name = "Calibri"
    r_pan.font.size = Pt(10)
    r_pan.font.bold = True
    r_pan.font.color.rgb = RGBColor(71, 85, 105)
    r_pname = p.add_run("Ms. Khiara Danielle Rubia")
    r_pname.font.name = "Calibri"
    r_pname.font.size = Pt(10)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(0)
    r = p.add_run("October 2026")
    r.font.name = "Calibri"
    r.font.size = Pt(11)
    r.font.bold = True
    r.font.color.rgb = RGBColor(100, 116, 139)

    doc.add_page_break()

    md_lines.extend([
        "## UNIVERSITY OF SAN JOSE-RECOLETOS",
        "",
        "School of Computer Studies",
        "Cebu City, Philippines",
        "# EVATRACK",
        "",
        "## Streamlined Evacuation Processes and Coordination Platform Integrating SafeTrack Demographics and ResQperation Response Support",
        "",
        "## Software Requirements Specification",
        "",
        "### Version 1.0",
        "",
        "A Capstone Project Presented to the Faculty of the",
        "School of Computer Studies",
        "University of San Jose-Recoletos",
        "In Partial Fulfillment of the Requirements for",
        "Bachelor of Science in Information Technology / Associate in Computer Technology",
        "### Presented by:",
        "",
        "Klint M. Ruales",
        "Danica Gelbolingo",
        "Anna Rhea Villadolid",
        "Vhenz Cernal",
        "Capstone Adviser: Mr. Stefan A.D. Flores",
        "Project Coordinator: Mr. Roderick A. Bandalan",
        "Panel Chair: Mr. Eric Magto  |  Panelist: Ms. Khiara Danielle Rubia",
        "### October 2026",
        "",
        ""
    ])

    def h(text, level):
        add_styled_heading(doc, text, level)
        md_lines.append(f"{'#' * level} {text}\n")

    def body(text, bold_prefix=None):
        add_body_p(doc, text, bold_prefix=bold_prefix)
        if bold_prefix:
            md_lines.append(f"{bold_prefix}{text}")
        else:
            md_lines.append(text)

    def bullet(text, bold_prefix=None):
        add_bullet(doc, text, bold_prefix=bold_prefix)
        if bold_prefix:
            md_lines.append(f"- {bold_prefix}{text}")
        else:
            md_lines.append(f"- {text}")

    def add_md_table(headers, rows):
        md_lines.append("")
        md_lines.append("| " + " | ".join(headers) + " |")
        md_lines.append("| " + " | ".join(["---"] * len(headers)) + " |")
        for r in rows:
            cleaned = [str(c).replace("\n", "<br>") for c in r]
            md_lines.append("| " + " | ".join(cleaned) + " |")
        md_lines.append("")

    # =========================================================================
    # REVISION HISTORY
    # =========================================================================
    h("Revision History", 1)
    rev_headers = ["Version", "Date", "Description", "Author(s)"]
    rev_data = [
        ("0.1", "2026-07-20", "Initial draft covering project scope, core system requirements, and stakeholder roles.", "All proponents"),
        ("0.5", "2026-08-20", "Defined functional and non-functional requirements and integration workflows with SafeTrack and ResQperation.", "Ruales, Gelbolingo, Villadolid, Cernal"),
        ("1.0", "2026-10-31", "Finalized SRS aligned with the actual implemented EvaTrack system, verified backend domain actions, frontend components, and active database schema.", "All proponents")
    ]
    rev_table = doc.add_table(rows=len(rev_data) + 1, cols=4)
    for c_idx, col_h in enumerate(rev_headers):
        rev_table.cell(0, c_idx).text = col_h
    for r_idx, row in enumerate(rev_data, start=1):
        for c_idx, val in enumerate(row):
            rev_table.cell(r_idx, c_idx).text = val
    style_table(rev_table, col_widths=[Inches(0.8), Inches(1.1), Inches(3.2), Inches(1.4)])
    add_md_table(rev_headers, rev_data)

    # =========================================================================
    # TABLE OF CONTENTS
    # =========================================================================
    h("Table of Contents", 1)
    toc_items = [
        ("Revision History", "2"),
        ("Table of Contents", "3"),
        ("1. Introduction", "4"),
        ("    1.1 Purpose", "4"),
        ("    1.2 Scope", "4"),
        ("    1.3 Definitions, Acronyms, and Abbreviations", "5"),
        ("    1.4 References", "6"),
        ("    1.5 Overview", "6"),
        ("2. Overall Description", "7"),
        ("    2.1 Product Perspective", "7"),
        ("    2.2 Product Functions", "8"),
        ("    2.3 User Classes and Characteristics", "8"),
        ("    2.4 Operating Environment", "9"),
        ("    2.5 Design and Implementation Constraints", "9"),
        ("    2.6 Assumptions and Dependencies", "10"),
        ("    2.7 Apportioning of Requirements", "10"),
        ("3. Specific Requirements", "11"),
        ("    3.1 External Interface Requirements", "11"),
        ("        3.1.1 User Interfaces", "11"),
        ("        3.1.2 Hardware Interfaces", "11"),
        ("        3.1.3 Software Interfaces", "12"),
        ("        3.1.4 Communications Interfaces", "12"),
        ("    3.2 Functional Requirements", "13"),
        ("        3.2.1 Authentication & User Account Management (AUTH)", "13"),
        ("        3.2.2 SafeTrack Demographics & Privacy Isolation (STK)", "14"),
        ("        3.2.3 Evacuation Center & Accommodation Unit Management (CTR)", "15"),
        ("        3.2.4 Household Verification & Check-In Admission (VER)", "16"),
        ("        3.2.5 In-Center Stay, Unit Allocation & Checkout (STAY)", "17"),
        ("        3.2.6 Disaster Event Management (EVT)", "18"),
        ("        3.2.7 Center Issue & Facility Condition Reporting (ISS)", "19"),
        ("        3.2.8 Resource Requests & ResQperation Coordination (RES)", "20"),
        ("        3.2.9 Emergency Alerts: SMS & Push Notifications (NOTIF)", "21"),
        ("        3.2.10 Evacuation Analytics & Report Exports (ANL)", "22"),
        ("        3.2.11 Super Admin System Oversight & In-App Alerts (OVR)", "23"),
        ("    3.3 Non-Functional Requirements", "24"),
        ("        3.3.1 Performance", "24"),
        ("        3.3.2 Security & Data Confidentiality", "24"),
        ("        3.3.3 Reliability and Availability", "25"),
        ("        3.3.4 Usability & Accessibility", "25"),
        ("        3.3.5 Maintainability & Architecture", "26"),
        ("        3.3.6 Portability", "26"),
        ("        3.3.7 Data Privacy Compliance", "26"),
        ("    3.4 Database Requirements", "27"),
        ("Appendix A. Entity-Relationship Overview", "30"),
        ("Appendix B. Requirements Traceability Matrix", "31"),
        ("Appendix C. Capstone Approval & Sign-Off Sheet", "34")
    ]
    for title, page in toc_items:
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(1)
        p.paragraph_format.space_after = Pt(1)
        p.paragraph_format.line_spacing = 1.15
        r_t = p.add_run(title)
        r_t.font.name = "Calibri"
        r_t.font.size = Pt(10)
        if not title.startswith("    "):
            r_t.font.bold = True
            r_t.font.color.rgb = RGBColor(30, 58, 138)
        else:
            r_t.font.color.rgb = RGBColor(51, 65, 85)
        r_dot = p.add_run(" " + "." * max(2, (75 - len(title) * 2)) + " ")
        r_dot.font.color.rgb = RGBColor(203, 213, 225)
        r_p = p.add_run(page)
        r_p.font.name = "Calibri"
        r_p.font.size = Pt(9.5)
        r_p.font.bold = True
        r_p.font.color.rgb = RGBColor(71, 85, 105)
        md_lines.append(f"{title.strip()} ........................................... {page}")
    md_lines.append("")

    doc.add_page_break()

    # =========================================================================
    # 1. INTRODUCTION
    # =========================================================================
    h("1. Introduction", 1)

    h("1.1 Purpose", 2)
    body(
        "This Software Requirements Specification (SRS) defines the functional and non-functional requirements for EVATRACK (Version 1.0) — a web-based evacuation management and coordination platform. This document serves as the official technical reference for the student proponents, the capstone adviser, the project coordinator, and the evaluation panel of the School of Computer Studies at the University of San Jose-Recoletos (USJ-R). Every requirement described in this document directly reflects the features, workflows, and database structure implemented in the EvaTrack system.",
        bold_prefix="Purpose and Objective: "
    )

    h("1.2 Scope", 2)
    body(
        "EvaTrack is a web-based platform designed to help Local Government Units (LGUs) and barangay disaster response teams manage evacuation centers efficiently during disasters. The system handles the complete evacuation workflow: setting up evacuation centers and accommodation units, verifying and admitting arriving households via QR code scanning or manual search, assigning families to shelter units, monitoring shelter occupancy in real time, reporting facility issues, requesting supplies, broadcasting emergency alerts, and generating demographic analytics.",
        bold_prefix="System Scope: "
    )
    body(
        "EvaTrack connects with two partner systems in the disaster management workflow:\n"
        "(1) SafeTrack Demographics System: EvaTrack retrieves registered household profiles, family member lists, addresses, and vulnerability indicators from SafeTrack. To protect resident privacy, this demographic information is hidden from the public and is accessible only to authorized system operators for verifying evacuees and computing shelter analytics.\n"
        "(2) ResQperation Response Platform: EvaTrack forwards resource and personnel assistance requests from evacuation centers to ResQperation and uses push notification services to send emergency evacuation alerts to residents with the mobile app."
    )
    body(
        "The system enforces Role-Based Access Control (RBAC) across three operator roles: Super Admin, Evacuation Admin, and Evacuation Personnel. Residents do not need user accounts; they can view public shelter availability on the EvaTrack Public Portal map, receive SMS and push alerts, and present their QR code upon arriving at an evacuation center.",
        bold_prefix="User Roles & Access: "
    )

    h("1.3 Definitions, Acronyms, and Abbreviations", 2)
    defs_headers = ["Term / Acronym", "Definition"]
    defs = [
        ("EvaTrack", "The web-based evacuation center management and coordination platform described in this document."),
        ("SafeTrack", "The partner demographic profiling system that provides registered household and family member records."),
        ("ResQperation", "The partner disaster response platform that receives resource requests and delivers mobile push alerts."),
        ("Accommodation Unit", "A designated living space inside an evacuation center (Family Room, Dormitory, Tent, Classroom, Gymnasium, or Covered Court) with a specific maximum capacity."),
        ("RBAC", "Role-Based Access Control — restricts system features and center data based on the logged-in user's role."),
        ("QR Code", "Quick Response code containing a household's unique identifier used for fast check-in verification at an evacuation center."),
        ("SMS", "Short Message Service — text messages sent via the TextBee SMS gateway to notify residents who do not have internet access."),
        ("Push Notification", "Mobile app alerts sent via OneSignal / ResQperation to notify residents who have internet connectivity."),
        ("PWD", "Person with Disability — a vulnerable group tracked by the system to help prioritize shelter care and analytics."),
        ("RA 10173", "Republic Act No. 10173 (Data Privacy Act of 2012, Philippines), governing the protection of personal household data."),
        ("REST API", "Representational State Transfer Application Programming Interface used for communication between the React frontend, Laravel backend, and partner systems."),
        ("DTO", "Data Transfer Object — a structured PHP object used in the backend to pass validated request data cleanly into domain actions.")
    ]
    def_table = doc.add_table(rows=len(defs) + 1, cols=2)
    def_table.cell(0, 0).text = defs_headers[0]
    def_table.cell(0, 1).text = defs_headers[1]
    for r_idx, (term, desc) in enumerate(defs, start=1):
        def_table.cell(r_idx, 0).text = term
        def_table.cell(r_idx, 1).text = desc
    style_table(def_table, col_widths=[Inches(1.8), Inches(4.7)])
    add_md_table(defs_headers, defs)

    h("1.4 References", 2)
    bullet("Systems and software engineering — Life cycle processes — Requirements engineering.", bold_prefix="ISO/IEC/IEEE 29148:2018: ")
    bullet("Data Privacy Act of 2012, National Privacy Commission, Republic of the Philippines.", bold_prefix="Republic Act No. 10173: ")
    bullet("Guidelines on Evacuation Center Coordination and Management, NDRRMC / DSWD / DILG / DOH.", bold_prefix="Joint Memorandum Circular No. 1 (2013): ")
    bullet("Laravel 11.x/12.x Official Documentation, https://laravel.com/docs.", bold_prefix="Laravel Framework Reference: ")
    bullet("React 18.x & TanStack React Query Documentation, https://react.dev.", bold_prefix="React Frontend Reference: ")
    bullet("MySQL 8.0 Reference Manual, Oracle Corporation, https://dev.mysql.com/doc/.", bold_prefix="MySQL 8.0 Database Reference: ")
    bullet("EvaTrack Capstone Project Baseline Requirements, School of Computer Studies, USJ-R.", bold_prefix="EVATRACK-Must-Haves (1).docx: ")
    md_lines.append("")

    h("1.5 Overview", 2)
    body(
        "Section 2 provides an overall description of the EvaTrack platform, including its architecture, core features, user roles, operating environment, and constraints. Section 3 details the specific requirements, covering user interfaces, software integrations, functional requirements for all 11 system modules, non-functional requirements, and the active MySQL database tables. Appendices A, B, and C present the Entity-Relationship overview, the Requirements Traceability Matrix mapped to the actual codebase, and the Capstone Approval Sheet."
    )

    doc.add_page_break()

    # =========================================================================
    # 2. OVERALL DESCRIPTION
    # =========================================================================
    h("2. Overall Description", 1)

    h("2.1 Product Perspective", 2)
    body(
        "EvaTrack is built as a modern web application with a clear separation between the frontend user interface, the backend API, and the relational database:\n"
        "(a) Frontend Application (EvaTrackFrontEnd): Built with React 18, Vite, and Tailwind CSS. It uses TanStack React Query for client-side data caching and instant page transitions, Zustand for reactive user and permission state, Leaflet for interactive evacuation center maps, Recharts for demographic charts, and an HTML5 camera scanner for reading household QR codes.\n"
        "(b) Backend API (EvaTrack): Built with Laravel and organized into modular domains (Authentication, Households, EvacuationCenters, AccommodationUnits, Evacuations, EvacuationEvents, CenterIssueReports, ResourceRequests, Notifications, Analytics, and ReferenceData). Each feature is handled by dedicated Controller, Action, Form Request, DTO, and Eloquent Repository classes.\n"
        "(c) Database Layer (mysql_v2 / shared_db): A MySQL 8.0 relational database containing the 31 active tables used by the system, enforcing foreign key relationships and soft deletes.",
        bold_prefix="System Architecture: "
    )
    body(
        "EvaTrack connects to SafeTrack data to verify households and members without exposing private records on public pages. It also connects with ResQperation to forward resource requests and send mobile push alerts, alongside TextBee for sending SMS alerts to residents without internet access.",
        bold_prefix="System Integrations: "
    )

    h("2.2 Product Functions", 2)
    body("EvaTrack provides the following core functions:")
    bullet("Retrieves household profiles, family members, addresses, and vulnerability groups from SafeTrack strictly for authorized personnel verification.", bold_prefix="SafeTrack Demographic Access: ")
    bullet("Verifies arriving households through camera-based QR code scanning or manual search by household name, member name, or contact number.", bold_prefix="Household Verification & Check-In: ")
    bullet("Tracks the status of households (Not Evacuated, Evacuated, Checked Out) and records which individual family members are present in the center.", bold_prefix="Evacuation Status Tracking: ")
    bullet("Automatically updates evacuation center occupancy and accommodation unit slot counts whenever households are admitted, assigned to units, or checked out.", bold_prefix="Automatic Capacity & Occupancy Updates: ")
    bullet("Allows operators to manage evacuation centers and configure accommodation units (Family Room, Dormitory, Tent, Classroom, Gymnasium, Covered Court).", bold_prefix="Center & Accommodation Unit Management: ")
    bullet("Enables personnel to submit resource and personnel requests and routes them to Evacuation Admins and ResQperation.", bold_prefix="Resource Request Routing: ")
    bullet("Allows personnel to report in-center facility, medical, sanitation, or security issues with severity levels and photo attachments, and lets admins update their resolution status.", bold_prefix="Center Issue Reporting: ")
    bullet("Sends emergency evacuation alerts via SMS (TextBee) for offline residents and Push Notifications (OneSignal / ResQperation) for mobile app users.", bold_prefix="Multi-Channel Emergency Alerts: ")
    bullet("Computes live evacuation statistics from verified evacuees, including age groups (infants, children, teens, adults, seniors), pregnant women, PWDs, gender counts, and shelter occupancy rates, with CSV and PDF export.", bold_prefix="Demographic & Vulnerability Analytics: ")
    bullet("Provides a public map portal where citizens can view active evacuation centers and available capacity without logging in.", bold_prefix="Public Evacuation Map Portal: ")
    md_lines.append("")

    h("2.3 User Classes and Characteristics", 2)
    user_headers = ["User Class", "System Responsibilities & Scope", "Access Level"]
    users_data = [
        ("Super Admin",
         "Has full oversight across all evacuation centers, disaster events, households, and reports. Exclusively authorized to register new user accounts (POST /api/users). Can manage centers, units, personnel assignments, and perform emergency overrides across all system modules.",
         "Full system-wide administrative access."),
        ("Evacuation Admin",
         "Creates and updates evacuation centers, configures accommodation units, and assigns Evacuation Personnel to centers. Declares and closes disaster events, monitors shelter occupancy, updates center issue statuses, reviews resource requests, and sends SMS and push alerts.",
         "Administrative access across evacuation centers and operations."),
        ("Evacuation Personnel",
         "Assigned to a specific evacuation center. Scans household QR codes or searches records to verify and admit evacuees, creates and manages accommodation units within their assigned center, assigns households to units, updates member presence, processes household checkouts, reports center issues, and submits resource requests.",
         "Operational access scoped to their assigned evacuation center."),
        ("Households / Residents\n(Passive Actor)",
         "View active evacuation centers and available slots on the Public Portal map, receive SMS and push evacuation alerts, and present their QR code or household details to personnel upon arrival at a shelter.",
         "Public portal access only; no login account required."),
        ("SafeTrack System\n(External System)",
         "Provides pre-registered household records, family member details, addresses, and vulnerability classifications for verification and analytics.",
         "Authenticated API and shared database integration."),
        ("ResQperation System\n(External System)",
         "Receives resource and personnel assistance requests from EvaTrack and delivers push notification alerts to residents.",
         "Authenticated REST API integration.")
    ]
    user_table = doc.add_table(rows=len(users_data) + 1, cols=3)
    for c_idx, col_h in enumerate(user_headers):
        user_table.cell(0, c_idx).text = col_h
    for r_idx, (role, desc, prof) in enumerate(users_data, start=1):
        user_table.cell(r_idx, 0).text = role
        user_table.cell(r_idx, 1).text = desc
        user_table.cell(r_idx, 2).text = prof
    style_table(user_table, col_widths=[Inches(1.5), Inches(3.6), Inches(1.4)])
    add_md_table(user_headers, users_data)

    h("2.4 Operating Environment", 2)
    bullet("PHP 8.2+, Laravel 11/12 framework, Composer 2.x, and MySQL 8.0 database server.", bold_prefix="Backend Server Environment: ")
    bullet("Modern web browsers (Google Chrome, Microsoft Edge, Mozilla Firefox, Apple Safari) on desktop computers, laptops, tablets, and smartphones.", bold_prefix="Client Browser Environment: ")
    bullet("Device camera (smartphone, tablet, or webcam) supporting browser media permissions for QR code scanning.", bold_prefix="Hardware Peripherals: ")
    bullet("HTTPS network connection for API communication, TextBee SMS gateway dispatch, and OneSignal / ResQperation push notification delivery.", bold_prefix="Network & Gateways: ")
    md_lines.append("")

    h("2.5 Design and Implementation Constraints", 2)
    bullet("The backend uses Laravel organized into domain folders with Controllers, Actions, Form Requests, DTOs, Models, and Eloquent Repositories.", bold_prefix="Backend Architecture: ")
    bullet("The frontend uses React 18, Vite, Tailwind CSS, TanStack React Query for caching, and Zustand for role and capability state management.", bold_prefix="Frontend Architecture: ")
    bullet("All database queries use Laravel Eloquent ORM or parameterized queries to prevent SQL injection.", bold_prefix="Database Security: ")
    bullet("In compliance with RA 10173 (Data Privacy Act of 2012), SafeTrack household and member records are protected behind authenticated operator routes and are never shown on the public portal.", bold_prefix="Data Privacy Protection: ")
    md_lines.append("")

    h("2.6 Assumptions and Dependencies", 2)
    bullet("Local Government Units register official evacuation centers and assign personnel accounts before or during disaster operations.", bold_prefix="Operational Setup: ")
    bullet("Household and family member demographic records from SafeTrack are available in the database for verification.", bold_prefix="SafeTrack Data Availability: ")
    bullet("ResQperation API endpoints, OneSignal push services, and the TextBee SMS gateway are reachable when dispatching external requests and alerts.", bold_prefix="External Service Connectivity: ")
    md_lines.append("")

    h("2.7 Apportioning of Requirements", 2)
    body(
        "Requirements marked as 'Must' in Section 3 represent the essential features required by the EVATRACK Must-Haves specification and implemented in the system. Requirements marked as 'Should' represent supporting usability and reporting features that improve day-to-day operations."
    )

    doc.add_page_break()

    # =========================================================================
    # 3. SPECIFIC REQUIREMENTS
    # =========================================================================
    h("3. Specific Requirements", 1)

    h("3.1 External Interface Requirements", 2)

    h("3.1.1 User Interfaces", level=3)
    bullet("Responsive web interface built with React and Tailwind CSS that adapts cleanly across mobile phones, tablets, and desktop screens, including a mobile bottom navigation bar and desktop sidebar.", bold_prefix="UI-01 Responsive Layout: ")
    bullet("Supports switching between Light Mode and Dark Mode, saving the user's preference in browser storage.", bold_prefix="UI-02 Light & Dark Theme: ")
    bullet("Displays active shelter capacities, available slots, occupancy ratios, active issue counts, and pending resource logistics on the operator Dashboard.", bold_prefix="UI-03 Command Dashboard: ")
    bullet("Provides searchable, filterable, and paginated tables across Evacuation Centers, Accommodation Units, Households, Issue Reports, Resource Requests, Alerts, and User Management.", bold_prefix="UI-04 Standardized Data Tables: ")
    bullet("Provides an interactive camera modal in the Verify Household module for scanning household QR codes, alongside an instant manual search bar.", bold_prefix="UI-05 QR Scanner & Verification View: ")
    bullet("Provides a Public Portal with an interactive Leaflet map showing evacuation center locations, capacity status, and routing guidance for citizens.", bold_prefix="UI-06 Public Evacuation Map Portal: ")
    md_lines.append("")

    h("3.1.2 Hardware Interfaces", level=3)
    bullet("Uses smartphone, tablet, or computer cameras through the browser MediaDevices API to scan household QR codes.", bold_prefix="HW-01 Camera Device Interface: ")
    bullet("Uses the browser print and file download interface to export PDF and CSV reports for centers and analytics.", bold_prefix="HW-02 Export & Print Interface: ")
    md_lines.append("")

    h("3.1.3 Software Interfaces", level=3)
    sw_headers = ["Interface Name", "Purpose in EvaTrack", "Integration Method"]
    sw_data = [
        ("SafeTrack Demographics Integration",
         "Provides household profiles, family member rosters, addresses, and vulnerability groups (Senior Citizen, PWD, Pregnant, Infant, Child) for verification.",
         "REST API (ExternalHouseholdService) and shared MySQL database tables."),
        ("ResQperation Resource & Push Integration",
         "Receives resource and personnel requests from evacuation centers and supports push notification alerts to residents.",
         "REST API JSON requests and OneSignalService push notification dispatch."),
        ("TextBee SMS Gateway",
         "Sends SMS evacuation alerts to registered household phone numbers for residents without internet access.",
         "HTTPS REST API via TextBeeService."),
        ("Leaflet & OpenStreetMap",
         "Renders interactive maps for selecting evacuation center coordinates and displaying shelter locations on the Public Portal.",
         "React-Leaflet map components and reverse geocoding utility.")
    ]
    sw_table = doc.add_table(rows=len(sw_data) + 1, cols=3)
    for c_idx, col_h in enumerate(sw_headers):
        sw_table.cell(0, c_idx).text = col_h
    for r_idx, (name, scope, proto) in enumerate(sw_data, start=1):
        sw_table.cell(r_idx, 0).text = name
        sw_table.cell(r_idx, 1).text = scope
        sw_table.cell(r_idx, 2).text = proto
    style_table(sw_table, col_widths=[Inches(1.8), Inches(3.0), Inches(1.7)])
    add_md_table(sw_headers, sw_data)

    h("3.1.4 Communications Interfaces", level=3)
    bullet("All API requests between the React frontend and Laravel backend use JSON over HTTP/HTTPS.", bold_prefix="COM-01 JSON REST Communication: ")
    bullet("Protected API routes use Laravel Sanctum bearer tokens and session authentication, automatically clearing sessions on 401 Unauthorized responses.", bold_prefix="COM-02 Token Authentication: ")
    bullet("All state-changing requests include CSRF protection headers (X-XSRF-TOKEN) and server-side validation.", bold_prefix="COM-03 Request Security: ")
    md_lines.append("")

    # =========================================================================
    # 3.2 FUNCTIONAL REQUIREMENTS
    # =========================================================================
    h("3.2 Functional Requirements", 2)
    body(
        "The functional requirements below are organized by system module. Every requirement corresponds directly to implemented features in EvaTrack and the baseline requirements in EVATRACK-Must-Haves (1).docx."
    )

    def add_fr_table(heading_text, reqs):
        h(heading_text, 3)
        headers = ["Req. ID", "Requirement Description", "Priority", "Attribution"]
        t = doc.add_table(rows=len(reqs) + 1, cols=4)
        for c_idx, col_h in enumerate(headers):
            t.cell(0, c_idx).text = col_h
        for r_idx, (r_id, desc, prio, attr) in enumerate(reqs, start=1):
            t.cell(r_idx, 0).text = r_id
            t.cell(r_idx, 1).text = desc
            t.cell(r_idx, 2).text = prio
            t.cell(r_idx, 3).text = attr
        style_table(t, col_widths=[Inches(1.1), Inches(3.6), Inches(0.7), Inches(1.1)])
        add_md_table(headers, reqs)

    # 3.2.1 AUTH
    auth_reqs = [
        ("FR-AUTH-01", "The system shall authenticate users (Super Admin, Evacuation Admin, Evacuation Personnel) using their registered email/credentials and hashed password, supporting OTP verification.", "Must", "Anna Rhea Villadolid"),
        ("FR-AUTH-02", "The system shall enforce Role-Based Access Control (RBAC) on both backend API routes and frontend views based on the user's assigned role.", "Must", "Anna Rhea Villadolid"),
        ("FR-AUTH-03", "The system shall restrict Evacuation Personnel to managing operations only within their assigned evacuation center (assigned_center_id).", "Must", "Anna Rhea Villadolid"),
        ("FR-AUTH-04", "The system shall allow only the Super Admin to register new user accounts (POST /api/users), while allowing Super Admins and Evacuation Admins to update users and assign personnel to centers.", "Must", "Anna Rhea Villadolid"),
        ("FR-AUTH-05", "The system shall revoke the user's authentication token immediately upon logout.", "Must", "Anna Rhea Villadolid"),
        ("FR-AUTH-06", "The system shall allow logged-in users to update their profile details, profile photo, and account password.", "Should", "Anna Rhea Villadolid")
    ]
    add_fr_table("3.2.1 Authentication & User Account Management (AUTH)", auth_reqs)

    # 3.2.2 STK
    stk_reqs = [
        ("FR-STK-01", "The system shall retrieve household records from SafeTrack, including the household name, contact number, address hierarchy (region, province, city, barangay, sitio, purok), and family member list.", "Must", "Vhenz Cernal"),
        ("FR-STK-02", "The system shall retrieve vulnerability classifications for household members (Senior Citizen, PWD, Pregnant, Infant, Child).", "Must", "Vhenz Cernal"),
        ("FR-STK-03", "The system shall restrict access to SafeTrack household and member demographic records strictly to authenticated system operators for verification and analytics.", "Must", "Vhenz Cernal"),
        ("FR-STK-04", "The system shall hide personal household demographic data from the public portal so that residents and unauthenticated visitors cannot view private citizen information.", "Must", "Vhenz Cernal"),
        ("FR-STK-05", "The system shall allow authorized operators to manage household profiles and family member records when updating registry details.", "Should", "Vhenz Cernal")
    ]
    add_fr_table("3.2.2 SafeTrack Demographics & Privacy Isolation (STK)", stk_reqs)

    # 3.2.3 CTR
    ctr_reqs = [
        ("FR-CTR-01", "The system shall allow Super Admins and Evacuation Admins to create, update, and delete evacuation centers with a center name, address, map coordinates (latitude/longitude), contact number, and maximum capacity.", "Must", "Vhenz Cernal"),
        ("FR-CTR-02", "The system shall allow Super Admins, Evacuation Admins, and assigned Evacuation Personnel to create, update, and delete accommodation units within an evacuation center, selecting a unit type (Family Room, Dormitory, Tent, Classroom, Gymnasium, Covered Court) and unit max capacity.", "Must", "Danica Gelbolingo"),
        ("FR-CTR-03", "The system shall validate that the total capacity of accommodation units inside a center does not exceed the evacuation center's maximum capacity.", "Must", "Danica Gelbolingo"),
        ("FR-CTR-04", "The system shall automatically update the center's current occupancy and available slots whenever households are admitted, updated, or checked out.", "Must", "Vhenz Cernal"),
        ("FR-CTR-05", "The system shall display real-time occupancy progress bars and status indicators (Available, Near Full, Full) for each evacuation center and accommodation unit.", "Must", "Vhenz Cernal"),
        ("FR-CTR-06", "The system shall allow Super Admins and Evacuation Admins to assign Evacuation Personnel to a specific evacuation center.", "Must", "Danica Gelbolingo")
    ]
    add_fr_table("3.2.3 Evacuation Center & Accommodation Unit Management (CTR)", ctr_reqs)

    # 3.2.4 VER
    ver_reqs = [
        ("FR-VER-01", "The system shall allow Evacuation Personnel to verify arriving households by scanning their QR code using the device camera.", "Must", "Klint M. Ruales"),
        ("FR-VER-02", "The system shall provide a manual search option to find and verify households by household name, member name, household ID, or contact number.", "Must", "Klint M. Ruales"),
        ("FR-VER-03", "The system shall display the household's family member list during verification so personnel can check in the specific members who are physically present.", "Must", "Klint M. Ruales"),
        ("FR-VER-04", "The system shall create an evacuation record and update the household's status to 'Evacuated' (household_status_id = 2) once members are checked in.", "Must", "Vhenz Cernal"),
        ("FR-VER-05", "The system shall disable the check-in action and show an 'Admitted' status once all members of a household have been verified and admitted.", "Must", "Klint M. Ruales"),
        ("FR-VER-06", "The system shall record the verifying personnel's user ID, the active disaster event ID, the admission timestamp, and the verification method (QR or manual).", "Must", "Klint M. Ruales"),
        ("FR-VER-07", "The system shall prevent duplicate active admissions of the same household across different evacuation centers during the same disaster event.", "Must", "Klint M. Ruales")
    ]
    add_fr_table("3.2.4 Household Verification & Check-In Admission (VER)", ver_reqs)

    # 3.2.5 STAY
    stay_reqs = [
        ("FR-STAY-01", "The system shall allow Evacuation Personnel and Admins to assign admitted households to an available accommodation unit (unit_allocations) within the evacuation center.", "Must", "Danica Gelbolingo"),
        ("FR-STAY-02", "The system shall allow personnel to unassign or transfer a household from an accommodation unit when needed.", "Must", "Danica Gelbolingo"),
        ("FR-STAY-03", "The system shall track and display the household's check-in date and time and monitor their stay inside the evacuation center.", "Must", "Danica Gelbolingo"),
        ("FR-STAY-04", "The system shall allow personnel to update individual family member evacuation status (Evacuated vs. Not Evacuated) during their stay, automatically adjusting the center's evacuated count.", "Must", "Danica Gelbolingo"),
        ("FR-STAY-05", "The system shall allow personnel to process household checkout, setting the household status to 'Checked Out' (household_status_id = 6) and recording the checkout timestamp.", "Must", "Danica Gelbolingo"),
        ("FR-STAY-06", "The system shall automatically release the household's assigned accommodation unit slots and deduct their member count from the center's active occupancy upon checkout.", "Must", "Vhenz Cernal")
    ]
    add_fr_table("3.2.5 In-Center Stay, Unit Allocation & Checkout (STAY)", stay_reqs)

    # 3.2.6 EVT
    evt_reqs = [
        ("FR-EVT-01", "The system shall allow Super Admins and Evacuation Admins to declare a disaster event by specifying the event name, disaster type (e.g., Typhoon, Flood, Earthquake, Fire), and geographic scope.", "Must", "Vhenz Cernal"),
        ("FR-EVT-02", "The system shall allow admins to assign operational evacuation centers to an active disaster event.", "Must", "Vhenz Cernal"),
        ("FR-EVT-03", "The system shall display the count of active emergencies and closed historical incidents on the Disaster Events management page.", "Must", "Klint M. Ruales"),
        ("FR-EVT-04", "The system shall allow Super Admins and Evacuation Admins to end an active disaster event, recording the end timestamp and moving it to the historical events log.", "Must", "Vhenz Cernal"),
        ("FR-EVT-05", "The system shall allow operators to filter evacuation records, issue reports, resource requests, alerts, and analytics by disaster event.", "Should", "Klint M. Ruales")
    ]
    add_fr_table("3.2.6 Disaster Event Management (EVT)", evt_reqs)

    # 3.2.7 ISS
    iss_reqs = [
        ("FR-ISS-01", "The system shall allow Evacuation Personnel and Admins to submit center issue reports categorized by issue category (e.g., Facility Damage, Sanitation, Medical/Health, Security).", "Must", "Anna Rhea Villadolid"),
        ("FR-ISS-02", "The system shall record the issue title, description, severity level (low, medium, high, critical), reporting user, and assigned evacuation center.", "Must", "Anna Rhea Villadolid"),
        ("FR-ISS-03", "The system shall allow Super Admins and Evacuation Admins to update the status of center issue reports (open, in_progress, resolved, closed).", "Must", "Anna Rhea Villadolid"),
        ("FR-ISS-04", "The system shall allow Evacuation Personnel to edit or delete their own reported issues while the issue status is still 'open'.", "Should", "Anna Rhea Villadolid"),
        ("FR-ISS-05", "The system shall display active center issue counts and severity breakdowns on the Dashboard and Analytics pages.", "Must", "Klint M. Ruales")
    ]
    add_fr_table("3.2.7 Center Issue & Facility Condition Reporting (ISS)", iss_reqs)

    # 3.2.8 RES
    res_reqs = [
        ("FR-RES-01", "The system shall allow Evacuation Personnel and Admins to submit resource requests specifying the request type (resource or personnel), item/resource name, quantity needed, description, and evacuation center.", "Must", "Anna Rhea Villadolid"),
        ("FR-RES-02", "The system shall route resource and personnel assistance requests to ResQperation (target_agency = 'ResQperation') for external response support.", "Must", "Anna Rhea Villadolid"),
        ("FR-RES-03", "The system shall track the status of each resource request (pending, approved, fulfilled, rejected) and display them in a searchable, filterable table.", "Must", "Anna Rhea Villadolid"),
        ("FR-RES-04", "The system shall allow external response updates or authorized administrators to update resource request fulfillment statuses.", "Must", "Anna Rhea Villadolid"),
        ("FR-RES-05", "The system shall summarize pending and fulfilled resource requests per center on the Dashboard and Analytics modules.", "Must", "Klint M. Ruales")
    ]
    add_fr_table("3.2.8 Resource Requests & ResQperation Coordination (RES)", res_reqs)

    # 3.2.9 NOTIF
    notif_reqs = [
        ("FR-NOTIF-01", "The system shall provide an Evacuation Alerts interface where Super Admins, Evacuation Admins, and Evacuation Personnel can create and send emergency broadcast alerts.", "Must", "Klint M. Ruales"),
        ("FR-NOTIF-02", "The system shall send SMS alerts via the TextBee SMS gateway (TextBeeService) to registered household contact numbers for residents without internet access.", "Must", "Klint M. Ruales"),
        ("FR-NOTIF-03", "The system shall send mobile push notifications via OneSignal / ResQperation (OneSignalService) to residents with registered device tokens.", "Must", "Klint M. Ruales"),
        ("FR-NOTIF-04", "The system shall support targeting alerts by evacuation center, disaster event, or barangay, and support instant, scheduled, or recurring dispatches.", "Must", "Klint M. Ruales"),
        ("FR-NOTIF-05", "The system shall log each broadcast in notifications, notification_recipients, and notification_logs, tracking delivery status (sent, failed, pending).", "Must", "Klint M. Ruales")
    ]
    add_fr_table("3.2.9 Emergency Alerts: SMS & Push Notifications (NOTIF)", notif_reqs)

    # 3.2.10 ANL
    anl_reqs = [
        ("FR-ANL-01", "The system shall compute real-time evacuation analytics from verified evacuation records and evacuated household members via LiveAnalyticsService.", "Must", "Klint M. Ruales"),
        ("FR-ANL-02", "The system shall calculate age group breakdowns of evacuated members (Infants 0–2, Children 3–12, Teens 13–17, Adults 18–59, Seniors 60+).", "Must", "Klint M. Ruales"),
        ("FR-ANL-03", "The system shall calculate the total number of pregnant women admitted across evacuation centers.", "Must", "Klint M. Ruales"),
        ("FR-ANL-04", "The system shall calculate the total number of Persons with Disabilities (PWDs) and Senior Citizens admitted.", "Must", "Klint M. Ruales"),
        ("FR-ANL-05", "The system shall calculate gender distribution counts (Male vs. Female) among verified evacuees.", "Must", "Klint M. Ruales"),
        ("FR-ANL-06", "The system shall calculate total evacuated households, total evacuated individuals, and center capacity utilization rates.", "Must", "Klint M. Ruales"),
        ("FR-ANL-07", "The system shall allow filtering analytics by Evacuation Center, Disaster Event, and Date Range, and support exporting reports to PDF and CSV.", "Must", "Klint M. Ruales")
    ]
    add_fr_table("3.2.10 Evacuation Analytics & Report Exports (ANL)", anl_reqs)

    # 3.2.11 OVR
    ovr_reqs = [
        ("FR-OVR-01", "The system shall grant the Super Admin full visibility and management access across all evacuation centers, disaster events, households, issue reports, and resource requests.", "Must", "Klint M. Ruales"),
        ("FR-OVR-02", "The system shall allow the Super Admin to override center-level restrictions to edit, admit, checkout, or clean up records across any evacuation center during emergencies.", "Must", "Klint M. Ruales"),
        ("FR-OVR-03", "The system shall generate in-app user notifications (user_notifications) to alert operators about new issue reports, resource request updates, and system events.", "Must", "Klint M. Ruales"),
        ("FR-OVR-04", "The system shall allow Super Admins and Evacuation Admins to export consolidated center and barangay-wide evacuation reports.", "Must", "Klint M. Ruales")
    ]
    add_fr_table("3.2.11 Super Admin System Oversight & In-App Alerts (OVR)", ovr_reqs)

    doc.add_page_break()

    # =========================================================================
    # 3.3 NON-FUNCTIONAL REQUIREMENTS
    # =========================================================================
    h("3.3 Non-Functional Requirements", 2)

    h("3.3.1 Performance", 3)
    bullet("Standard API requests for center details, household lists, and analytics shall respond within 1.5 seconds under normal operational load.", bold_prefix="NFR-PER-01 API Response Time: ")
    bullet("The frontend shall cache API responses using TanStack React Query so that revisiting previously loaded pages (Dashboard, Centers, Households, Analytics) renders data immediately without redundant loading spinners.", bold_prefix="NFR-PER-02 Client-Side Caching: ")
    bullet("QR code scanning and household lookup shall resolve and display the family member roster within 1 second.", bold_prefix="NFR-PER-03 QR Verification Speed: ")
    md_lines.append("")

    h("3.3.2 Security & Data Confidentiality", 3)
    bullet("All user passwords must be hashed using bcrypt before being stored in the users table.", bold_prefix="NFR-SEC-01 Password Hashing: ")
    bullet("All database queries must use Eloquent ORM or prepared statements to prevent SQL injection.", bold_prefix="NFR-SEC-02 SQL Injection Prevention: ")
    bullet("SafeTrack household and member records must only be accessible through authenticated API endpoints and must never be exposed on public routes.", bold_prefix="NFR-SEC-03 Demographic Data Isolation: ")
    bullet("Role permissions must be verified on both the backend controller/middleware layer and the frontend capability matrix.", bold_prefix="NFR-SEC-04 Role Verification: ")
    md_lines.append("")

    h("3.3.3 Reliability and Availability", 3)
    bullet("Database operations that modify multiple tables (such as admitting a household, recording evacuated members, and updating center occupancy) must run safely so partial failures do not corrupt capacity counts.", bold_prefix="NFR-REL-01 Data Consistency: ")
    bullet("If a household's QR code is damaged or a camera is unavailable, personnel can immediately use the manual search bar to verify and admit the household.", bold_prefix="NFR-REL-02 Manual Verification Fallback: ")
    bullet("Core tables (users, households, household_members, evacuation_centers, accommodation_units, disaster_events) use soft deletes (deleted_at) so historical records are not permanently lost by accident.", bold_prefix="NFR-REL-03 Soft Deletion Recovery: ")
    md_lines.append("")

    h("3.3.4 Usability & Accessibility", 3)
    bullet("The user interface provides consistent tables, status badges, confirmation modals, and clear error messages so field personnel can operate the system with minimal training.", bold_prefix="NFR-USA-01 Clear Operator Workflow: ")
    bullet("Supports both Light Mode and Dark Mode with readable text contrast for indoor or night-time shelter operations.", bold_prefix="NFR-USA-02 Visual Readability: ")
    md_lines.append("")

    h("3.3.5 Maintainability & Architecture", 3)
    bullet("The Laravel backend follows a Domain-Driven folder structure where each module has its own Controllers, Actions, DTOs, Requests, Models, and Repositories.", bold_prefix="NFR-MNT-01 Modular Backend Domains: ")
    bullet("The React frontend separates business logic into custom hooks (e.g., useEvacuationDetail, useCenterUnits, useCenterHouseholds, useVerifyHousehold) and reusable UI components (Button, Card, Table, Modal).", bold_prefix="NFR-MNT-02 Clean Frontend Architecture: ")
    md_lines.append("")

    h("3.3.6 Portability", 3)
    bullet("The web application runs in any modern desktop or mobile web browser without requiring users to install a native app.", bold_prefix="NFR-POR-01 Cross-Device Web Access: ")
    bullet("Database schema definitions and seeders are maintained in version control for consistent setup across development and production servers.", bold_prefix="NFR-POR-02 Reproducible Setup: ")
    md_lines.append("")

    h("3.3.7 Data Privacy Compliance", 3)
    bullet("Complies with Republic Act No. 10173 (Data Privacy Act of 2012) by limiting household demographic access strictly to authorized disaster response personnel.", bold_prefix="NFR-CMP-01 RA 10173 Compliance: ")
    bullet("Publicly accessible pages (Landing and Public Portal) display only aggregated shelter capacities and facility locations, keeping all personal citizen data private.", bold_prefix="NFR-CMP-02 Public Privacy Protection: ")
    md_lines.append("")

    # =========================================================================
    # 3.4 DATABASE REQUIREMENTS
    # =========================================================================
    h("3.4 Database Requirements", 2)
    body(
        "The EvaTrack database (shared_db / mysql_v2) is implemented in MySQL 8.0 using the InnoDB engine. The system actively uses the following core database tables organized by functional domain:"
    )

    db_headers = ["Table Name", "System Purpose & Description", "Key Relationships"]
    db_entities = [
        ("users", "Stores system operator accounts (Super Admin, Evacuation Admin, Evacuation Personnel) and their assigned center.", "Belongs to roles and evacuation_centers; has many user_notifications"),
        ("roles", "Defines the 3 system roles: super_admin (1), evac_admin (2), and evac_personnel (3).", "Has many users"),
        ("user_notifications", "Stores in-app notification alerts for logged-in system operators.", "Belongs to users"),
        ("households", "Master household records from SafeTrack, including household head name, contact number, and status.", "Belongs to addresses; has many household_members and evacuation_records"),
        ("household_members", "Individual family member records including full name, birthdate, gender, civil status, and relationship.", "Belongs to households, genders, civil_statuses, relationships; has many member_vulnerable_groups"),
        ("household_statuses", "Lookup statuses for households and members (1 = Not Evacuated, 2 = Evacuated, 6 = Checked Out).", "Referenced by evacuation_records and evacuated_members"),
        ("vulnerable_groups & member_vulnerable_groups", "Defines vulnerability categories (Senior Citizen, PWD, Pregnant, Infant, Child) and links them to members.", "Many-to-many between household_members and vulnerable_groups"),
        ("addresses & location tables", "Stores household addresses linked to regions, provinces, cities, barangays, sitios, puroks, and zipcodes.", "Referenced by households"),
        ("disaster_events, disaster_types & severity_levels", "Stores declared disaster events, their disaster type (Typhoon, Flood, Fire, etc.), severity level, and start/end dates.", "Has many evacuation_records, notifications, and analytics"),
        ("evacuation_centers & center_occupancies", "Stores registered evacuation shelters, GPS coordinates, max capacity, current event link, and live occupancy counts.", "Has many accommodation_units, evacuation_records, center_issue_reports, and resource_requests"),
        ("accommodation_types", "Lookup table for unit types (Family Room, Dormitory, Tent, Classroom, Gymnasium, Covered Court).", "Has many accommodation_units"),
        ("accommodation_units", "Individual rooms, tents, or halls inside an evacuation center with a specific unit type and max capacity.", "Belongs to evacuation_centers and accommodation_types; has many unit_allocations"),
        ("unit_allocations", "Records the assignment of an evacuated household (evacuation_id) to a specific accommodation unit (unit_id).", "Belongs to evacuation_records, accommodation_units, and users (assigned_by)"),
        ("evacuation_records", "Records a household's check-in admission to an evacuation center during a disaster event, including evacuated_count and checkout_at.", "Belongs to households, evacuation_centers, disaster_events, and household_statuses; has many evacuated_members"),
        ("evacuated_members", "Tracks each individual family member's presence status (Evacuated vs. Not Evacuated) for a specific evacuation record.", "Belongs to evacuation_records, household_members, and household_statuses"),
        ("center_issue_reports, center_issue_categories & center_issue_report_statuses", "Stores facility, medical, sanitation, and security issues reported at an evacuation center along with severity and resolution status.", "Belongs to evacuation_centers and users (reported_by)"),
        ("resource_requests & resource_request_statuses", "Stores supply and personnel assistance requests submitted by centers and routed to ResQperation.", "Belongs to evacuation_centers and users (requested_by)"),
        ("notifications, notification_recipients, notification_logs & device_tokens", "Stores SMS and Push broadcast alerts, target household recipients, device push tokens, and delivery logs.", "Belongs to evacuation_centers and disaster_events; links to households"),
        ("analytics, analytics_job_logs & analytics_job_statuses", "Stores computed demographic and shelter occupancy snapshots and background analytics job logs.", "Belongs to evacuation_centers and disaster_events")
    ]
    db_table = doc.add_table(rows=len(db_entities) + 1, cols=3)
    for c_idx, col_h in enumerate(db_headers):
        db_table.cell(0, c_idx).text = col_h
    for r_idx, (ent, desc, rel) in enumerate(db_entities, start=1):
        db_table.cell(r_idx, 0).text = ent
        db_table.cell(r_idx, 1).text = desc
        db_table.cell(r_idx, 2).text = rel
    style_table(db_table, col_widths=[Inches(1.8), Inches(2.9), Inches(1.8)])
    add_md_table(db_headers, db_entities)

    body(
        "Foreign key constraints maintain referential integrity across all related tables. Soft deletes (deleted_at) are used on core records so historical evacuation data and analytics remain intact.",
        bold_prefix="Data Integrity & Archival: "
    )

    doc.add_page_break()

    # =========================================================================
    # APPENDIX A. ENTITY-RELATIONSHIP OVERVIEW
    # =========================================================================
    h("Appendix A. Entity-Relationship Overview", 1)
    body(
        "The EvaTrack database centers around the evacuation_records table, which connects a SafeTrack household (households) to an evacuation shelter (evacuation_centers) under a declared disaster event (disaster_events). When a household is verified, individual family members are recorded in evacuated_members, and the center's live headcount is updated in center_occupancies. Within each shelter, living spaces are organized into accommodation_units (typed by accommodation_types) and assigned to households through unit_allocations. Operational needs at each shelter are tracked through center_issue_reports and resource_requests, while public advisories are recorded in notifications, notification_recipients, and notification_logs. The complete Entity-Relationship Diagram (ERD) is documented in evatrack_erd_reference.md."
    )

    # =========================================================================
    # APPENDIX B. REQUIREMENTS TRACEABILITY MATRIX
    # =========================================================================
    h("Appendix B. Requirements Traceability Matrix", 1)
    body(
        "The table below maps each Must-Have requirement from EVATRACK-Must-Haves (1).docx to its Functional Requirement ID, the actual backend and frontend files in the EvaTrack codebase, and the responsible team member:"
    )

    trace_headers = ["Baseline Must-Have Requirement", "FR Mapping", "Actual System Module (Backend & Frontend)", "Proponent"]
    trace_data = [
        ("Retrieve SafeTrack demographic data for identification & verification only; data hidden from households.",
         "FR-STK-01\nFR-STK-03\nFR-STK-04",
         "HouseholdController.php\nSearchHouseholdsAction.php\nExternalHouseholdService.php",
         "Vhenz Cernal"),
        ("Track household evacuation status as Evacuated or Not Evacuated.",
         "FR-VER-04\nFR-STAY-04",
         "EvacuationController.php\nUpdateEvacuatedMemberStatusAction.php\nHouseholdDetail.jsx",
         "Vhenz Cernal"),
        ("Automatically update evacuation center capacity when household is verified.",
         "FR-CTR-04\nFR-STAY-06",
         "ScanQREvacuationAction.php\nVerifyManualEvacuationAction.php\nCenterOccupancy.php",
         "Vhenz Cernal"),
        ("Compute evacuation analytics from demographics (age, pregnant, PWD, gender, affected pop).",
         "FR-ANL-01\nto FR-ANL-07",
         "AnalyticsController.php\nLiveAnalyticsService.php\nAnalytics.jsx",
         "Klint M. Ruales"),
        ("Route alerts and assistance requests to households and ResQperation.",
         "FR-RES-02\nFR-NOTIF-02\nFR-NOTIF-03",
         "NotificationService.php\nTextBeeService.php\nOneSignalService.php",
         "Anna Rhea Villadolid"),
        ("Evacuation Personnel: Log in (evacuation personnel role).",
         "FR-AUTH-01\nFR-AUTH-02\nFR-AUTH-03",
         "AuthController.php\nLogin.jsx\nuseUserStore.ts / roles.ts",
         "Anna Rhea Villadolid"),
        ("Verify evacuated households via QR code scanning or manual input.",
         "FR-VER-01\nFR-VER-02\nFR-VER-03",
         "ScanQREvacuationAction.php\nVerifyManualEvacuationAction.php\nVerifyHousehold.jsx",
         "Klint M. Ruales"),
        ("Update household info post-verification: assign units, monitor stay, update status.",
         "FR-STAY-01\nto FR-STAY-05",
         "UnitAllocationController.php\nAssignUnitAllocationAction.php\nCheckoutEvacuationAction.php\nEvacuationDetail.jsx",
         "Danica Gelbolingo"),
        ("Report in-center needs (facility issues, resource shortages, and personnel requests).",
         "FR-ISS-01\nFR-RES-01",
         "CenterIssueReportController.php\nCreateCenterIssueReportAction.php\nCenterIssueReports.jsx",
         "Anna Rhea Villadolid"),
        ("Request resources and personnel assistance from ResQperation.",
         "FR-RES-01\nFR-RES-02\nFR-RES-03",
         "ResourceRequestController.php\nCreateResourceRequestAction.php\nResourceRequests.jsx",
         "Anna Rhea Villadolid"),
        ("Evacuation Admin: Log in (evacuation admin role).",
         "FR-AUTH-01\nFR-AUTH-02",
         "AuthController.php\nLogin.jsx\nuseUserStore.ts / roles.ts",
         "Anna Rhea Villadolid"),
        ("Create and configure evacuation centers.",
         "FR-CTR-01\nFR-CTR-05",
         "EvacuationCenterController.php\nCreateEvacuationCenterAction.php\nEvacuationList.jsx",
         "Vhenz Cernal"),
        ("Create and configure accommodation units (rooms/tents/halls) within an evacuation center.",
         "FR-CTR-02\nFR-CTR-03",
         "AccommodationUnitController.php\nCreateAccommodationUnitAction.php\nUnitModal.jsx",
         "Danica Gelbolingo"),
        ("Monitor ongoing evacuations across centers (status, occupancy, personnel activity).",
         "FR-CTR-05\nFR-EVT-03",
         "EvacuationCenterController.php\nDashboard.jsx\nEvacuationList.jsx",
         "Klint M. Ruales"),
        ("Monitor ongoing requests per evacuation center.",
         "FR-RES-03\nFR-RES-05",
         "ListResourceRequestsAction.php\nResourceRequests.jsx",
         "Anna Rhea Villadolid"),
        ("Send evacuation alerts (SMS via TextBee for non-internet; push via OneSignal/ResQperation).",
         "FR-NOTIF-01\nto FR-NOTIF-05",
         "NotificationController.php\nNotificationService.php\nEvacuationAlerts.jsx",
         "Klint M. Ruales"),
        ("Super Admin: User registration, system-wide reports, full oversight, and emergency override access.",
         "FR-AUTH-04\nFR-OVR-01\nto FR-OVR-04",
         "UserController.php\nAnalyticsExportController.php\nUserManagement.jsx",
         "Klint M. Ruales")
    ]
    trace_table = doc.add_table(rows=len(trace_data) + 1, cols=4)
    for c_idx, col_h in enumerate(trace_headers):
        trace_table.cell(0, c_idx).text = col_h
    for r_idx, (must, fr, mod, prop) in enumerate(trace_data, start=1):
        trace_table.cell(r_idx, 0).text = must
        trace_table.cell(r_idx, 1).text = fr
        trace_table.cell(r_idx, 2).text = mod
        trace_table.cell(r_idx, 3).text = prop
    style_table(trace_table, col_widths=[Inches(2.3), Inches(1.0), Inches(2.0), Inches(1.2)])
    add_md_table(trace_headers, trace_data)

    doc.add_page_break()

    # =========================================================================
    # APPENDIX C. APPROVAL & SIGN-OFF SHEET
    # =========================================================================
    h("Appendix C. Capstone Approval & Sign-Off Sheet", 1)
    body(
        "By signing below, the capstone adviser, project coordinator, panel evaluators, and student proponents attest that this Software Requirements Specification has been reviewed and approved as the official baseline for EVATRACK (Version 1.0)."
    )

    sign_headers = ["Role in Capstone Project", "Full Name & Academic Title", "Signature / Date"]
    signatories = [
        ("Student Proponent (Lead)", "Klint M. Ruales", "_________________________"),
        ("Student Proponent", "Danica Gelbolingo", "_________________________"),
        ("Student Proponent", "Anna Rhea Villadolid", "_________________________"),
        ("Student Proponent", "Vhenz Cernal", "_________________________"),
        ("Capstone Project Adviser", "Mr. Stefan A.D. Flores", "_________________________"),
        ("Capstone Project Coordinator", "Mr. Roderick A. Bandalan", "_________________________"),
        ("Panel Evaluation Chair", "Mr. Eric Magto", "_________________________"),
        ("Panel Evaluator", "Ms. Khiara Danielle Rubia", "_________________________")
    ]
    sign_table = doc.add_table(rows=len(signatories) + 1, cols=3)
    for c_idx, col_h in enumerate(sign_headers):
        sign_table.cell(0, c_idx).text = col_h
    for r_idx, (role, name, sig) in enumerate(signatories, start=1):
        sign_table.cell(r_idx, 0).text = role
        sign_table.cell(r_idx, 1).text = name
        sign_table.cell(r_idx, 2).text = sig
    style_table(sign_table, col_widths=[Inches(2.2), Inches(2.3), Inches(2.0)])
    add_md_table(sign_headers, signatories)

    # Save DOCX
    doc.save(output_docx_path)
    print(f"Successfully generated DOCX SRS at: {output_docx_path}")

    # Save MD if requested
    if output_md_path:
        with open(output_md_path, "w", encoding="utf-8") as f:
            f.write("\n".join(md_lines) + "\n")
        print(f"Successfully generated Markdown SRS at: {output_md_path}")


if __name__ == "__main__":
    out_docx = "c:/CAPSTONE/EvaTrack/docs/EVATRACK-SRS-v1.0.docx"
    out_md = "c:/CAPSTONE/EvaTrack/docs/EVATRACK-SRS-v1.0.md"
    build_srs_document(out_docx, out_md)
