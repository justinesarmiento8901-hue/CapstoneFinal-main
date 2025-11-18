<div class="container mt-4">
    <h3>Vaccination Schedule (Under 1 Year)</h3>
    <form id="vaccineForm">
        <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Bakuna</th>
                    <th>Sakit na Maiiwasan</th>
                    <th>Pagkapanganak</th>
                    <th>1½ Buwan</th>
                    <th>2½ Buwan</th>
                    <th>3½ Buwan</th>
                    <th>9 Buwan</th>
                    <th>1 Taon</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Example data — eventually pulled from tbl_vaccine_reference
                $vaccines = [
                    ['BCG', 'Tuberculosis (TB)', ['Birth']],
                    ['Hepatitis B', 'Hepatitis B', ['Birth']],
                    ['Pentavalent (DPT-HepB-Hib)', 'Diphtheria, Pertussis, Tetanus, Hep B, Hib', ['1½ mo', '2½ mo', '3½ mo']],
                    ['Oral Polio (OPV)', 'Polio', ['1½ mo', '2½ mo', '3½ mo']],
                    ['Inactivated Polio (IPV)', 'Polio', ['3½ mo']],
                    ['Pneumococcal Conjugate Vaccine (PCV)', 'Pneumonia, Meningitis', ['1½ mo', '2½ mo', '3½ mo']],
                    ['Measles, Mumps, Rubella (MMR)', 'Tigdas, Beke, German Measles', ['9 mo', '1 yr']],
                ];

                foreach ($vaccines as $v) {
                    echo "<tr>";
                    echo "<td>{$v[0]}</td>";
                    echo "<td>{$v[1]}</td>";

                    $ages = ['Birth', '1½ mo', '2½ mo', '3½ mo', '9 mo', '1 yr'];
                    foreach ($ages as $a) {
                        $checked = in_array($a, $v[2]) ? '' : 'disabled'; // disable if not applicable
                        echo "<td><input type='checkbox' name='vaccines[{$v[0]}][$a]' $checked></td>";
                    }

                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="text-end">
            <button type="submit" class="btn btn-success">Save Schedule</button>
        </div>
    </form>
</div>

<script>
    $('#vaccineForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'save_vaccine_status.php',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Vaccination record updated successfully.'
                });
            }
        });
    });
</script>