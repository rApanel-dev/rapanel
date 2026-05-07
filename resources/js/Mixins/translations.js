export default {
    methods: {
        __(key, replacements = {}) {
            // Buscamos en los props de Inertia, NO en window._translations
            // El ?. evita que la página se ponga en blanco si aún no cargan las traducciones
            let translation = this.$page.props.translations?.[key] || key;

            Object.keys(replacements).forEach(r => {
                translation = translation.replace(`:${r}`, replacements[r]);
            });

            return translation;
        }
    }
};
