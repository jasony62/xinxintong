{
	"pages": [{
		"name": "enroll",
		"title": "登记页",
		"type": "I"
	}, {
		"name": "list",
		"title": "登记清单页",
		"type": "I"
	}],
	"entryRule": {
		"otherwise": {
			"entry": "enroll"
		},
		"member": {
			"entry": "enroll",
			"enroll": "Y",
			"remark": "Y"
		},
		"member_outacl": {
			"entry": "enroll",
			"enroll": "Y",
			"remark": "Y"
		},
		"fan": {
			"entry": "enroll",
			"enroll": "Y",
			"remark": "Y"
		},
		"nonfan": {
			"entry": "$mp_follow",
			"enroll": "$mp_follow"
		}
	},
	"enrolled_entry_page": "enroll"
}