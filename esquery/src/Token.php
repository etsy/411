<?php

namespace ESQuery;

class Token {
    // Commands
    const C_SEARCH = 0;
    const C_AGG = 1;
    const C_JOIN = 2;
    const C_TRANS = 3;

    // Filters
    const F_AND = 0;
    const F_OR = 1;
    const F_NOT = 2;
    const F_IDS = 3;
    const F_EXISTS = 4;
    const F_MISSING = 5;
    const F_QUERY = 6;
    const F_RANGE = 7;
    const F_REGEX = 8;
    const F_PREFIX = 9;
    const F_TERM = 10;
    const F_TERMS = 11;

    // Queries
    const Q_WILDCARD = 20;
    const Q_QUERYSTRING = 21;

    // Custom
    const X_LIST = 30;

    // Aggregations
    const A_TERMS = 0;
    const A_SIGTERMS = 1;
    const A_CARD = 2;
    const A_MIN = 3;
    const A_MAX = 4;
    const A_SUM = 5;
    const A_AVG = 6;

    // Wildcards
    const W_STAR = 0;
    const W_QMARK = 1;
}
