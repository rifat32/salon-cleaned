options=[
    // ===========================
    // PERSONAL DETAILS
    // ===========================
    // TYPE: text
    {
      id: "full_name",
      label: "full name",
      groupName: "personal_details",
      type: "text",
      defaultValue: filters?.full_name || "",
    },

    // TYPE: email
    {
      id: "email",
      label: "Email",
      groupName: "personal_details",
      type: "email",
      defaultValue: filters?.email || "",
    },
    // TYPE: text
    {
      id: "NI_number",
      label: "NI Number",
      groupName: "personal_details",
      type: "text",
      defaultValue: filters?.NI_number || "",
    },
    // TYPE: single-select
    {
      id: "gender",
      label: "Gender",
      groupName: "personal_details",
      type: "single-select",
      options: [
        { id: 0, label: "Male", value: "male" },
        { id: 1, label: "Female", value: "female" },
        { id: 2, label: "Other", value: "other" },
      ],
      defaultSelectedValues: filters?.gender || [],
    },

    // ===========================
    // EMPLOYMENT
    // ===========================
    // TYPE: range
    {
      id: "start_salary_per_annum",
      label: "Salary",
      groupName: "employment",
      type: "range",
      defaultSelectedValues: filters?.start_salary_per_annum || [],
    },
    {
        id: "end_salary_per_annum",
        label: "Salary",
        groupName: "employment",
        type: "range",
        defaultSelectedValues: filters?.end_salary_per_annum || [],
      },



    // TYPE: date-range
    {
      id: "start_joining_date",
      label: "Joining Date",
      groupName: "employment",
      type: "date-range",
      defaultSelectedValues: filters?.start_joining_date || [],
    },
    {
        id: "end_joining_date",
        label: "Joining Date",
        groupName: "employment",
        type: "date-range",
        defaultSelectedValues: filters?.end_joining_date || [],
      },
    // TYPE: multi-select
    {
      id: "designation_id",
      label: "Designation",
      groupName: "employment",
      type: "multi-select",
      options: combineData?.designations?.map((es) => ({
        id: es?.id,
        label: formatRole(es?.name),
        value: es?.id,
      })),
      defaultSelectedValues: filters?.designation_id || [],
    },
    // TYPE: number
    {
      id: "start_weekly_contractual_hours",
      label: "Weekly Contract Hours",
      groupName: "employment",
      type: "number",
      defaultValue: filters?.start_weekly_contractual_hours || "",
    },
    {
        id: "end_weekly_contractual_hours",
        label: "Weekly Contract Hours",
        groupName: "employment",
        type: "number",
        defaultValue: filters?.end_weekly_contractual_hours || "",
      },
    // TYPE: multi-select
    {
      id: "employment_status_id",
      label: "Employment Status",
      groupName: "employment",
      type: "multi-select",
      options: combineData?.employment_statuses?.map((es) => ({
        id: es?.id,
        label: formatRole(es?.name),
        value: es?.id,
      })),
      defaultSelectedValues: filters?.employment_status_id || [],
    },
    // TYPE: multi-select
    {
      id: "work_location_ids",
      label: "Work Site",
      groupName: "employment",
      type: "multi-select",
      options: combineData?.work_locations?.map((es) => ({
        id: es?.id,
        label: formatRole(es?.name),
        value: es?.id,
      })),
      defaultSelectedValues: filters?.work_location_id || [],
    },
    // TYPE: multi-select
    {
      id: "department_id",
      label: "Department",
      groupName: "employment",
      type: "multi-select",
      options: combineData?.departments?.map((es) => ({
        id: es?.id,
        label: formatRole(es?.name),
        value: es?.id,
      })),
      defaultSelectedValues: filters?.department_id || [],
    },
    // TYPE: multi-select
    {
      id: "recruitment_process_ids",
      label: "On Boarding Process",
      groupName: "employment",
      type: "multi-select",
      options: combineData?.recruitment_processes?.map((es) => ({
        id: es?.id,
        label: formatRole(es?.name),
        value: es?.id,
      })),
      defaultSelectedValues: filters?.recruitment_process_ids || [],
    },
    // TYPE: multi-select
    {
      id: "right_to_work_code",
      label: "Right To Work Status",
      groupName: "employment",
      type: "multi-select",
      options: combineData?.right_to_work_status?.map((es) => ({
        id: es?.id,
        label: formatRole(es?.name),
        value: es?.id,
      })),
      defaultSelectedValues: filters?.right_to_work_code || [],
    },
    // TYPE: multi-select
    {
      id: "pension_scheme_status",
      label: "Pension scheme status",
      groupName: "employment",
      type: "multi-select",
      options: [
        {
          id: 0,
          label: "Opt In",
          value: "opt_in",
        },
        {
          id: 1,
          label: "Opt Out",
          value: "opt_out",
        },
      ],
      defaultSelectedValues: filters?.pension_scheme_status || [],
    },

    // ===========================
    // LEAVE
    // ===========================
    // TYPE: multi-select
    {
      id: "leave_status",
      label: "Leave Status",
      groupName: "leave_details",
      type: "multi-select",
      options: [
        { id: 1, label: "Yes", value: 1 },
        { id: 0, label: "No", value: 0 },
      ],
      defaultSelectedValues: filters?.leave_status || [],
    },
    // TYPE: date-range
    {
      id: "start_leave_date",
      label: "Leave Dates",
      groupName: "leave_details",
      type: "date-range",
      defaultSelectedValues: filters?.start_leave_date || [],
    },
    {
        id: "end_leave_date",
        label: "Leave Dates",
        groupName: "leave_details",
        type: "date-range",
        defaultSelectedValues: filters?.end_leave_date || [],
      },

    // ===========================
    // HOLIDAY
    // ===========================
    // TYPE: multi-select
    {
      id: "holiday_status",
      label: "Holiday Status",
      groupName: "holiday_status",
      type: "multi-select",
      options: [
        { id: 1, label: "Yes", value: 1 },
        { id: 0, label: "No", value: 0 },
      ],
      defaultSelectedValues: filters?.holiday_status || [],
    },
    // TYPE: date-range
    {
      id: "start_holiday_date",
      label: "Holiday Dates",
      groupName: "holiday_details",
      type: "date-range",
      defaultSelectedValues: filters?.start_holiday_date || [],
    },
        // TYPE: date-range
        {
            id: "end_holiday_date",
            label: "Holiday Dates",
            groupName: "holiday_details",
            type: "date-range",
            defaultSelectedValues: filters?.end_holiday_date || [],
          },

    // ===========================
    // IMMIGRATION
    // ===========================
    // TYPE: multi-select
    {
      id: "immigration_status",
      label: "Immigration status",
      groupName: "immigration",
      type: "multi-select",
      options: [
        {
          id: 0,
          label: "British citizen",
          value: "british_citizen",
        },
        { id: 1, label: "ILR", value: "ilr" },
        { id: 2, label: "Immigrant", value: "immigrant" },
        { id: 3, label: "Sponsored", value: "sponsored" },
      ],
      defaultSelectedValues: filters?.immigration_status || [],
    },
    // TYPE: multi-select
    {
      id: "sponsorship_status",
      label: "Sponsorship status",
      groupName: "immigration",
      type: "multi-select",
      options: [
        { id: 0, label: "Unassigned", value: "unassigned" },
        { id: 1, label: "Assigned", value: "assigned" },
        { id: 2, label: "Visa applied", value: "visa_applied" },
        { id: 3, label: "Visa rejected", value: "visa_rejected" },
        { id: 4, label: "Visa grants", value: "visa_grantes" },
        { id: 5, label: "Withdrawal", value: "withdrawal" },
      ],
      defaultSelectedValues: filters?.sponsorship_status || [],
    },


    // TYPE: range
    {
      id: "start_visa_expiry_date",
      label: "Visa Expiry",
      groupName: "visa",
      type: "range",
      defaultSelectedValues: filters?.start_visa_expiry_date || [],
    },
    {
        id: "end_visa_expiry_date",
        label: "Visa Expiry",
        groupName: "visa",
        type: "range",
        defaultSelectedValues: filters?.end_visa_expiry_date || [],
      },
    // ===========================
    // PASSPORT
    // ===========================
    // TYPE: multi-select
    {
      id: "passport_number",
      label: "Passport status",
      groupName: "passport",

      defaultSelectedValues: filters?.passport_number || [],
    },
    // TYPE: range
    {
      id: "start_passport_expiry_date",
      label: "Passport Expiry",
      groupName: "passport",
      type: "range",
      defaultSelectedValues: filters?.start_passport_expiry_date || [],
    },
     // TYPE: range
     {
        id: "end_passport_expiry_date",
        label: "Passport Expiry",
        groupName: "passport",
        type: "range",
        defaultSelectedValues: filters?.end_passport_expiry_date || [],
      },

    // ===========================
    // SPONSORSHIP
    // ===========================

    // TYPE: text
    {
      id: "sponsorship_certificate_number",
      label: "Certificate number",
      groupName: "sponsorship",
      type: "number",
      defaultValue: filters?.sponsorship_certificate_number || "",
    },
    // TYPE: date-range
    {
      id: "start_sponsorship_date_assigned",
      label: "Date Assigned",
      groupName: "sponsorship",
      type: "date-range",
      defaultSelectedValues: filters?.start_sponsorship_date_assigned || [],
    },
     // TYPE: date-range
     {
        id: "end_sponsorship_date_assigned",
        label: "Date Assigned",
        groupName: "sponsorship",
        type: "date-range",
        defaultSelectedValues: filters?.end_sponsorship_date_assigned || [],
      },
    // TYPE: date-range
    {
      id: "start_sponsorship_expiry_date",
      label: "Date Of Expiry",
      groupName: "sponsorship",
      type: "date-range",
      defaultSelectedValues: filters?.start_sponsorship_expiry_date || [],
    },
    {
        id: "end_sponsorship_expiry_date",
        label: "Date Of Expiry",
        groupName: "sponsorship",
        type: "date-range",
        defaultSelectedValues: filters?.end_sponsorship_expiry_date || [],
      },
  ];




