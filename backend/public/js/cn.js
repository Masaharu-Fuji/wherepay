function cn() {
    const args = Array.from(arguments);

    const parts = args
        .flatMap(function (v) {
            if (!v) {
                return [];
            }

            if (typeof v === "string") {
                return v.split(/\s+/);
            }

            if (Array.isArray(v)) {
                return v;
            }

            if (typeof v === "object") {
                return Object
                    .entries(v)
                    .filter(function (entry) {
                        const value = entry[1];

                        return !!value;
                    })
                    .map(function (entry) {
                        const key = entry[0];

                        return key;
                    });
            }

            return [];
        })
        .filter(Boolean);

    return parts
        .join(" ");
}
