# GrumPHP Prettier

A [Prettier](https://prettier.io/) task for [GrumPHP](https://github.com/phpro/grumphp).

## Installation

```shell-script
composer require indykoning/grumphp-prettier
```

And at the very least you need prettier installed in your node_modules folder.

```shell-script
yarn add prettier
```
## Usage

In your grumphp.yml : 

```yaml
grumphp:
  extensions:
    - Indykoning\GrumPHPPrettier\ExtensionLoader
  tasks:
    prettier:
        # These are all optional
        command: "node_modules/.bin/prettier"
        triggered_by: ["js", "ts", "jsx", "tsx", "vue", "css", "less", "scss", "sass", "html", "blade.php", "antlers", "phtml"]
        ignore_patterns: []

        # Whether it should immediately fix instead of asking to fix
        auto_fix: false
        # If auto fix is enabled should we stage it as well?
        # With both on it will fix and add the fixed files while you're committing
        auto_stage: false

        # These optional settings pass arguments to prettier, overwriting your .prettierrc
        # https://prettier.io/docs/en/options.html
        ignore_unknown: true
        arrow_parens:
        ignore_unknown:
        arrow_parens:
        bracket_same_line:
        no_bracket_spacing:
        end_of_line:
        html_whitespace_sensitivity:
        parser:
        print_width:
        prose_wrap:
        quote_props:
        no_semi:
        single_attribute_per_line:
        single_quote:
        tab_width:
        trailing_comma:
        use_tabs:
        vue_indent_script_and_style:
```
