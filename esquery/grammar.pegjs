//<?php

// Root definitions
Root
  = _? settings:(Settings _)? first:QueryCommand rest:(_? '|' _? TailCommand)* _? {
    return [
      isset($settings) ? $settings[0]:[],
      Util::combine($first, $rest, 3)
    ];
  }

TailCommand
  = JoinCommand
  / AggCommand
  / TransactionCommand


// Settings
Settings
  = first:Setting rest:(_ Setting)* {
    return Util::assoc($first, $rest, 1);
  }

Setting
  = '$' ret:(
    key:'date_field' SEP val:Field { return [$key, $val]; } /
    key:'to' SEP val:Date { return [$key, $val]; } /
    key:'from' SEP val:Date { return [$key, $val]; } /
    key:'size' SEP val:Integer { return [$key, $val]; } /
    key:'flatten' SEP val:Boolean { return [$key, $val]; } /
    key:'allow_leading_wildcard' SEP val:Boolean { return [$key, $val]; } /
    key:'sort' SEP '[' val:SortsSetting ']' { return [$key, $val]; } /

    key:'fields' SEP '[' first:Field rest:(_ Field)* ']' { return [$key, Util::combine($first, $rest, 1)]; } /
    key:'map' SEP '[' val:MapSetting ']' { return [$key, $val]; } /
  ) { return $ret; }

MapSetting
  = _? first:FieldMap rest:(_? ',' _? FieldMap)* _? { return Util::assoc($first, $rest, 3); }

FieldMap
  = field:Field new_field:(SEP Field) { return [$field, $new_field[1]]; }

SortsSetting
  = _? first:FieldSort rest:(_? ',' _? FieldSort)* _? { return Util::combine($first, $rest, 3); }

FieldSort
  = field:Field order:(SEP Order)? { return [$field, isset($order) ? $order[1]:0]; }

Date
  = Integer
  / $[0-9a-zA-Z|/+-]+ // FIXME: Should be more strict.


Order
  = 'ASC' { return 0; }
  / 'DESC' { return 1; }


// Glue
SEP
  = ':'

_ "WhitespaceOrComment"
  = Whitespace? Comment Whitespace?
  / Whitespace

Comment
  = '#' [^\r\n]*

Whitespace
  = WhitespaceChar+

WhitespaceChar
  = [ \t\n\r]


// Field names and values
Field
  = $[a-zA-Z0-9\._\-]+

Value
  = $[a-zA-Z0-9\._\-]+

Boolean
  = 'true' { return true; }
  / 'false' { return false; }

Integer
  = num:[0-9]+ { return intval(implode('', $num)); }


// Special values
WildCardValue
  = '"' chrs:DoubleQuotedChar* '"' { return [implode('', $chrs)]; }
  / "'" chrs:SingleQuotedChar* "'" { return [implode('', $chrs)]; }
  / chunks:WildCardChunk* { return $chunks; }

WildCardChunk
  = '*' { return Token::W_STAR; }
  / '?' { return Token::W_QMARK; }
  / chrs:EscapedChar+ { return implode('', $chrs); }

EscapedChar
  = '\\' chr:MetaChar { return $chr; }
  / SpecialChar
  / !MetaChar chr:. { return $chr; }

LiteralValue
  = '"' chrs:DoubleQuotedChar* '"' { return implode('', $chrs); }
  / "'" chrs:SingleQuotedChar* "'" { return implode('', $chrs); }
  / chrs:LiteralChar+ { return implode('', $chrs); }

LiteralChar
  = EscapedChar

DoubleQuotedChar
  = '\\"' { return '"'; }
  / SpecialChar
  / '\\'? chr:[^"] { return $chr; }

SingleQuotedChar
  = "\\'" { return "'"; }
  / chr:[^'] { return $chr; }

MetaChar
  = chr:WhitespaceChar { return $chr[0]; }
  / '(' / ')'
  / '{' / '}'
  / '[' / ']'
  / '+'
  / '-'
  / ':'
  / '\\'
  / '/'
  / '@'
  / '^'
  / '|'
  / '"'
  / "'"
  / '*'
  / '?'

SpecialChar
  = '\\n' { return "\n"; }
  / '\\r' { return "\r"; }
  / '\\t' { return "\t"; }


// Query command
QueryCommand
  = query:QueryExpression { return [Token::C_SEARCH, $query]; }
  / '*' { return [Token::C_SEARCH, []]; }

QueryExpression
  = QueryOR

QueryOR
  = a:QueryAND b:(_ 'OR' _ QueryAND)* {
    if(isset($b) && count($b)) {
      return [Token::F_OR, Util::combine($a, $b, 3)];
    }
    return $a;
  }

QueryAND
  =  a:QueryNOT b:((_ 'AND')? _ QueryNOT)* {
    if(isset($b) && count($b)) {
      return [Token::F_AND, Util::combine($a, $b, 2)];
    }
    return $a;
  }

QueryNOT
  = neg:'-'? '(' _? expr:QueryExpression _? ')' {
    if($neg) {
      return [Token::F_NOT, $expr];
    }
    return $expr;
  }
  / neg:'-'? clause:QueryClause {
    if($neg) {
      $clause = [Token::F_NOT, $clause];
    }
    return $clause;
  }

QueryClause
  = '_exists_' SEP ret:(
    field:Field { return [Token::F_EXISTS, $field]; } /
    { $this->error('Invalid field'); }) { return $ret; }

  / '_missing_' SEP ret:(
    field:Field { return [Token::F_MISSING, $field]; } /
    { $this->error('Invalid field'); }) { return $ret; }

  / field:Field SEP a:QueryRangeLow _? ret:(
    lo:QueryRangeValue _ 'TO' _ hi:QueryRangeValue _? b:QueryRangeHigh
      { return [$b == ']', $lo, $hi]; } /
    { $this->error('Invalid range'); }) { return array_merge([Token::F_RANGE, $field, $a == '['], $ret); }

  / field:Field SEP '^' ret:(
    val:LiteralValue { return [$val]; } /
    { $this->error('Invalid prefix'); }) { return array_merge([Token::F_PREFIX, $field], $ret); }

  / field:Field SEP '/' ret:(
    regex:RegexValue '/' { return [$regex]; } /
    { $this->error('Invalid regex'); }) { return array_merge([Token::F_REGEX, $field], $ret); }

  / field:Field SEP '(' ret:(
    _? first:WildCardValue rest:(_ WildCardValue)* _? ')'
      { return [Util::combine($first, $rest, 1)]; } /
    { $this->error('Invalid list'); }) { return array_merge([Token::X_LIST, $field], $ret, [true]); }

  / field:Field SEP esc:('@' '@'?) ret:(
    list:Field
      { return [$list]; } /
    { $this->error('Invalid list name'); }) { return array_merge([Token::X_LIST, $field], $ret, [$esc[1] == '@']); }

  / field:Field SEP val:WildCardValue { return [Token::Q_QUERYSTRING, $field, $val]; }

RegexValue
  = chrs:RegexChar* { return implode('', $chrs); }

RegexChar
  = '\\/' { return '/'; }
  / [^/]

QueryRangeLow
  = '['
  / '{'

QueryRangeHigh
  = ']'
  / '}'

QueryRangeValue
  = '*' { return null; }
  / LiteralValue


// Join command
JoinCommand
  = 'join' _ 'source_field' SEP source:Field _ 'target_field' SEP target:Field query:(_ QueryExpression)? {
    return [Token::C_JOIN, $source, $target, isset($query) ? $query[1]:[]];
  }


// Agg command
AggCommand
  = 'agg' SEP type:AggType _ 'field' SEP field:Field settings:(_ AggSettings)? {
    return [Token::C_AGG, $type, $field, isset($settings) ? $settings[1]:[]];
  }

AggType
  = 'terms' { return Token::A_TERMS; }
  / 'sigterms' { return Token::A_SIGTERMS; }
  / 'card' { return Token::A_CARD; }
  / 'min' { return Token::A_MIN; }
  / 'max' { return Token::A_MAX; }
  / 'avg' { return Token::A_AVG; }
  / 'sum' { return Token::A_SUM; }

AggSettings
  = first:AggSetting rest:(_ AggSetting)* {
    return Util::assoc($first, $rest, 1);
  }

AggSetting
  = field:Field SEP val:Value { return [$field, is_numeric($val) ? (float)$val:$val]; }

// Transaction command
TransactionCommand
  = 'transaction' _ 'field' SEP field:Field { return [Token::C_TRANS, $field]; }
